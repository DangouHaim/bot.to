<?
$listings = $_POST["listings"];
$data = $_POST["data"];
?>

<?php if (!$data['template'] || in_array( $data['template'], ['grid', 'fluid-grid'] ) ): ?>
	<section class="i-section listing-feed">
		<div class="container-fluid">
			<div class="row section-body grid" data-target-type="<? if(!isset($_POST["isajax"])) echo $_POST["atts"]["targettype"] ?>" data-atts='<? echo json_encode($_POST["atts"]) ?>' data-page="<? echo $_POST["atts"]["page"]?>">
				<?php foreach ($listings as $listing): $listing->_c27_show_promoted_badge = $data['show_promoted_badge'] == true; ?>
					<?php c27()->get_partial('listing-preview', [
						'listing' => $listing,
						'wrap_in' => sprintf(
										'col-lg-%1$d col-md-%2$d col-sm-%3$d col-xs-%4$d grid-item',
										12 / absint( $data['columns']['lg'] ), 12 / absint( $data['columns']['md'] ),
										12 / absint( $data['columns']['sm'] ), 12 / absint( $data['columns']['xs'] )
									),
						]) ?>
				<?php endforeach ?>
			</div>
		</div>
	</section>
<?php endif ?>