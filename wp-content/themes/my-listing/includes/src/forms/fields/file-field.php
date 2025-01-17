<?php

namespace MyListing\Src\Forms\Fields;

if ( ! defined('ABSPATH') ) {
	exit;
}

class File_Field extends Base_Field {

	public function get_posted_value() {
		$files = isset( $_POST[ 'current_'.$this->key ] )
			? array_map( 'esc_url_raw', (array) $_POST[ 'current_'.$this->key ] )
			: [];

		return array_filter( $files );
	}

	public function validate() {
		$value = $this->get_posted_value();

		if ( $this->props['multiple'] && $this->props['file_limit'] && count( $value ) > absint( $this->props['file_limit'] ) ) {
			// translators: %1$d is the amount of files allowed; %2$s is the field label.
			throw new \Exception( sprintf( _x( 'You can\'t upload more than %1$d items in %2$s.', 'Add listing form', 'my-listing' ), absint( $this->props['file_limit'] ), $this->props['label'] ) );
		}

		foreach ( (array) $value as $file_url ) {
			if ( is_numeric( $file_url ) ) {
				continue;
			}

			// validate attachment urls
			$file_url = esc_url( $file_url, [ 'http', 'https' ] );
			if ( empty( $file_url ) ) {
				// translators: %s is the field label.
				throw new \Exception( sprintf( _x( 'Invalid attachment provided for %s.', 'Add listing form', 'my-listing' ), $this->props['label'] ) );
			}

			// validate attachment file types
			$file_url = current( explode( '?', $file_url ) );
			$file_info = wp_check_filetype( $file_url );

			if ( ! empty( $this->props['allowed_mime_types'] ) && $file_info && ! in_array( $file_info['type'], $this->props['allowed_mime_types'], true ) ) {
				// translators: Placeholder %1$s is the field label; %2$s is the file mime type; %3$s is the allowed mime-types.
				throw new \Exception( sprintf(
					_x( '"%1$s" (filetype %2$s) needs to be one of the following file types: %3$s', 'Add listing form', 'my-listing' ),
					$this->props['label'],
					$file_info['ext'],
					implode( ', ', array_keys( $this->props['allowed_mime_types'] ) )
				) );
			}
		}
	}

	public function update() {
		global $wpdb;

		$value = $this->get_posted_value();
		$old_value = get_post_meta( $this->listing->get_id(), '_'.$this->key, true );
		$attachment_ids = [];
		$attachment_urls = [];

		/**
		 * Prepare the selected files to be saved in the listing meta. Maintains backward
		 * compatibility, handles external images and offloaded media.
		 */
		foreach ( (array) $value as $attachment_url ) {
			// `guid` is a unique identifier that we can use to get attachments
			$row = $wpdb->get_row( $wpdb->prepare(
				"SELECT ID, post_parent, post_status FROM {$wpdb->posts} WHERE post_type = 'attachment' AND guid = %s LIMIT 1",
				$attachment_url
			) );

			/**
			 * Check if this file exists as an attachment already. If it doesn't, then attempt
			 * to create the attachment, but only if the file is writable and within the `uploads/` directory.
			 *
			 * If the file isn't writable, it's an external image, in which case, we can't add it as an attachment, since
			 * it won't be possible to generate all the different image sizes.
			 */
			if ( ! is_object( $row ) || empty( $row->ID ) ) {
				$attachment_urls[] = $attachment_url;
				if ( $attachment_id = $this->create_attachment( $attachment_url ) ) {
					$attachment_ids[ $attachment_url ] = $attachment_id;
				}
				continue;
			}

			/**
			 * If it's a new attachment, update it's status from `preview` to `inherit`,
			 * and set the `post_parent` to the listing it's being uploaded to.
			 */
			if ( $row->post_status === 'preview' ) {
				wp_update_post( [
					'ID' => $row->ID,
					'post_status' => 'inherit',
					'post_parent' => $this->listing->get_id(),
				] );
			}

			// attachment is valid, store it's ID
			$attachment_ids[ $attachment_url ] = absint( $row->ID );
			$attachment_urls[] = $attachment_url;
		}

		// update the field meta with the attachment urls
		update_post_meta( $this->listing->get_id(), '_'.$this->key, $attachment_urls );

		/**
		 * Delete unused attachments in $old_value. This behavior can be skipped using:
		 * `add_filter( 'mylisting/submission/delete-unused-attachments', '__return_false' );`
		 *
		 * @since 2.1
		 */
		foreach ( (array) $old_value as $attachment_url ) {
			if ( apply_filters( 'mylisting/submission/delete-unused-attachments', ! is_admin() ) !== true ) {
				continue;
			}

			$row = $wpdb->get_row( $wpdb->prepare(
				"SELECT ID, post_parent, post_status FROM {$wpdb->posts} WHERE post_type = 'attachment' AND guid = %s LIMIT 1",
				$attachment_url
			) );

			// validate attachment
			if ( ! is_object( $row ) || empty( $row->ID ) || absint( $row->post_parent ) !== $this->listing->get_id() ) {
				continue;
			}

			// if this attachment is not present in the new attachment ids list,
			// then it's been removed by the user, so we can delete it.
			if ( ! in_array( absint( $row->ID ), $attachment_ids ) ) {
				mlog()->warn( "Deleted attachment #{$row->ID} since it's no longer used by the listing." );
				wp_delete_attachment( absint( $row->ID ), true );
			}
		}
	}

	private function create_attachment( $file_url ) {
		include_once ABSPATH . 'wp-admin/includes/file.php';
		include_once ABSPATH . 'wp-admin/includes/media.php';
		include_once ABSPATH . 'wp-admin/includes/image.php';

		$upload_dir = wp_upload_dir();
		$filepath = str_replace(
			[ $upload_dir['baseurl'], $upload_dir['url'], WP_CONTENT_URL ],
			[ $upload_dir['basedir'], $upload_dir['path'], WP_CONTENT_DIR ],
			$file_url
		);

		// validate
		if ( ! strstr( $file_url, WP_CONTENT_URL ) || strstr( $filepath, 'http:' ) || strstr( $filepath, 'https:' ) || ! wp_is_writable( $filepath ) ) {
			mlog( sprintf( 'External or non-writable image used, skipping attachment. <a href="%s" target="_blank">[link]</a>', $file_url ) );
			return false;
		}

		// create attachment
		$attachment_id = wp_insert_attachment( [
			'post_title' => basename( $filepath ),
			'post_content' => '',
			'post_status' => 'inherit',
			'post_parent' => $this->listing->get_id(),
			'post_mime_type' => wp_check_filetype( basename( $filepath ) )['type'],
			'guid' => $file_url,
		], $filepath );

		if ( ! $attachment_id || is_wp_error( $attachment_id ) ) {
			return false;
		}

		// generate attachment details and sizes
		wp_update_attachment_metadata(
			$attachment_id,
			wp_generate_attachment_metadata( $attachment_id, $filepath )
		);

		mlog( 'Generated attachment for writable file #'.$attachment_id );
		return $attachment_id;
	}

	public function field_props() {
		$this->props['type'] = 'file';
		$this->props['ajax'] = true;
		$this->props['multiple'] = false;
		$this->props['file_limit'] = '';
		$this->props['allowed_mime_types'] = new \stdClass;
		$this->props['allowed_mime_types_arr'] = [];
	}

	public function get_editor_options() {
		$this->getLabelField();
		$this->getKeyField();
		$this->getPlaceholderField();
		$this->getDescriptionField();

		$this->getFileFieldSettings();

		$this->getRequiredField();
		$this->getShowInSubmitFormField();
		$this->getShowInAdminField();
	}

	/**
	 * Renders a "allowed mime types" setting in the field settings in the listing type editor.
	 *
	 * @since 1.0
	 */
	protected function getFileFieldSettings() { ?>
		<div class="form-group full-width" v-if="['job_logo', 'job_cover', 'job_gallery'].indexOf(field.slug) <= -1">
			<label>Allowed file types</label>
			<select multiple="multiple" v-model="field.allowed_mime_types_arr" @change="fieldsTab().editFieldMimeTypes($event, field)">
				<?php foreach ( \MyListing\Ext\Listing_Types\Editor::$store['mime-types'] as $extension => $mime ): ?>
					<option value="<?php echo "{$extension} => {$mime}" ?>"><?php echo $mime ?></option>
				<?php endforeach ?>
			</select>
			<br><br>
			<label><input type="checkbox" v-model="field.multiple"> Allow multiple files?</label>
		</div>
		<div v-show="field.multiple">
			<input type="number" v-model="field.file_limit" style="width: 50px; margin: 0;">
			<label>Maximum number of uploads allowed</label>
			<br><br>
		</div>
	<?php }
}