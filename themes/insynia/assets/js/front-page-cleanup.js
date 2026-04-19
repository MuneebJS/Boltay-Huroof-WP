( function () {
	var headingSelectors = 'h1, h2, h3';
	var textTargets = [ 'meet our team', 'meet the team' ];

	function normalize( value ) {
		return ( value || '' )
			replace( /\s+/g, ' ' )
			trim()
			.toLowerCase();
	}

	function findTeamHeading() {
		var headings = document.querySelectorAll( headingSelectors );
		for ( var i = 0; i < headings.length; i++ ) {
			var headingText = normalize( headings[ i ].textContent );
			if ( textTargets.indexOf( headingText ) !== -1 ) {
				return headings[ i ];
			}
		}

		return null;
	}

	function findRemovableContainer( heading ) {
		if ( ! heading ) {
			return null;
		}

		var selectors = [
			'section',
			'.wp-block-group.alignfull',
			'.wp-block-cover.alignfull',
			'.wp-block-group',
			'.elementor-section',
			'.vc_row'
		];

		for ( var i = 0; i < selectors.length; i++ ) {
			var candidate = heading.closest( selectors[ i ] );
			if ( candidate ) {
				return candidate;
			}
		}

		return null;
	}

	function removeTeamSection() {
		if ( ! document.body || ! document.body.classList.contains( 'home' ) ) {
			return;
		}

		var teamHeading = findTeamHeading();
		var section = findRemovableContainer( teamHeading );

		if ( section ) {
			section.remove();
		}
	}

	removeTeamSection();
} )();
