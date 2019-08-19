<div class="cts-logs-wrapper">
	<div class="cts-logs">
		<div class="cts-log-categories">
			<?php foreach ( $categories as $name => $count ): ?>
				<div class="cts-log-category cts-log-category-<?php echo esc_attr( $name ) ?> active" data-type="<?php echo esc_attr( $name ) ?>">
					<?php echo $name ?>
					<span><strong>(<?php echo $count ?>)</strong></span>
				</div>
			<?php endforeach ?>
			<span class="cts-logs-toggle pull-right">[x]</span>
		</div>
		<div class="cts-log-list">
			<?php foreach ( $this->logs as $log ): ?>
				<div class="cts-log cts-log-<?php echo esc_attr( $log['type'] ) ?>">
					<div class="cts-log-content">
						<span class="show-trace"><span class="trace-plus">[+]</span><span class="trace-minus">[-]</span></span>
						<?php echo $log['content'] ?>
					</div>
					<div class="cts-log-backtrace">
						<?php if ( ! empty( $log['url'] ) ): ?>
							<p class="cts-backtrace-url" name="<?php echo esc_attr( $log['url'] ) ?>"><?php echo $log['url'] ?></p>
						<?php endif ?>
						<?php echo $log['trace']; ?>
					</div>
				</div>
			<?php endforeach ?>
		</div>
	</div>
</div>

<style type="text/css">
	.cts-logs {
		position: fixed;
		z-index: 500000;
		bottom: 20px;
		left: 20px;
		width: 400px;
		border: 1px solid #eee;
		font-family: 'Roboto';
	    background: rgba(255, 255, 255, 0.99);
	    box-shadow: 0px 1px 45px -4px #0000002e;
	}

	.cts-log-list {
		overflow-x: hidden;
		overflow-y: auto;
		max-height: 500px;
	}

	.cts-log {
		padding: 4px;
		font-size: 11px;
		color: #2196F3;
		line-height: 1.5;
	}

	.cts-log:nth-child(2n) {
		background: rgba(238, 238, 238, 0.45);
	}

	.cts-log-warning {
		color: #f44336;
	}

	.cts-log-notice {
		color: #ffa000;
	}

	.cts-log-categories {
	    padding: 5px;
	}

	.cts-log-category {
		display: inline-block;
		vertical-align: top;
		font-size: 13px;
		text-transform: uppercase;
		padding: 5px 10px;
		cursor: pointer;
		border-radius: 5px;
		transition: .2s all;
	    background: rgba(238, 238, 238, 0.3);
	    color: #c5c5c5;
	}

	.cts-log-category.active,
	.cts-log-category:hover {
		background: rgba(33, 150, 243, 0.1);
		color: rgba(33, 150, 243, 0.96);
	}

	.cts-log-category-warning.active,
	.cts-log-category-warning:hover {
		background: rgba(244, 67, 54, 0.14);
		color: #F44336;
	}

	.cts-log-category-notice.active,
	.cts-log-category-notice:hover {
	    background: rgba(255, 236, 179, 0.63);
	    color: #ff7600;
	}

	.cts-backtrace-log {
		color: #555;
		font-size: 12px;
		margin-bottom: 2px;
		display: none;
	}

	.cts-log .show-trace {
		color: #888;
		font-size: 13px;
		cursor: pointer;
	}
	.cts-log.trace-active .cts-backtrace-log {
		display: block;
	}
	.cts-log .show-trace .trace-minus { display: none; }
	.cts-log.trace-active .show-trace .trace-plus { display: none; }
	.cts-log.trace-active .show-trace .trace-minus { display: inline; }
	.cts-backtrace-url {
	    font-weight: 500;
	    font-size: 10px;
	    text-overflow: ellipsis;
	    white-space: nowrap;
	    overflow: hidden;
	    color: #607D8B;
    	margin: 1px 5px 5px 15px;
    	line-height: 1.5;
	}

	.cts-logs-hidden .cts-log-category, .cts-logs-hidden .cts-log-list { display: none; }
	.cts-logs-hidden .cts-logs { width: auto; opacity: .5; }
	.cts-logs-toggle { cursor: pointer; }
</style>

<script type="text/javascript">
	(function() {
		var categories = document.querySelectorAll( '.cts-log-category' );
		var logs = document.querySelectorAll( '.cts-log' );
		var backtraces = document.querySelectorAll( '.cts-log .show-trace' );
		var wrapper = document.querySelector('.cts-logs-wrapper');
		var toggle = document.querySelector('.cts-logs-toggle');

		toggle.addEventListener( 'click', function() {
			wrapper.classList.toggle('cts-logs-hidden');
		} );

		categories.forEach( function( node ) {
			node.addEventListener( 'click', function() {
				var isActive = node.classList.toggle('active');
				logs.forEach( function( log ) {
					if ( ! log.classList.contains( 'cts-log-' + node.dataset.type ) ) {
						return;
					}
					log.style.display = isActive ? 'block' : 'none';
				} );
			} );
		} );

		backtraces.forEach( function( backtrace ) {
			backtrace.addEventListener( 'click', function() {
				var showTrace = backtrace.parentNode.parentNode.classList.toggle('trace-active');
				if ( showTrace ) {
					logs.forEach( function( log ) {
						if ( log === backtrace.parentNode.parentNode ) {
							return;
						}

						log.classList.remove('trace-active');
					} );
				}
			} );
		} );
	})();
</script>