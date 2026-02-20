module.exports = {
	extends: [ 'plugin:@wordpress/eslint-plugin/recommended' ],
	settings: {
		'import/resolver': {
			node: {
				extensions: [ '.js', '.jsx' ],
			},
		},
		'import/core-modules': [
			'@wordpress/api-fetch',
			'@wordpress/components',
			'@wordpress/data',
			'@wordpress/dom-ready',
			'@wordpress/element',
			'@wordpress/hooks',
			'@wordpress/i18n',
		],
	},
};
