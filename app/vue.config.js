const bootstrapSassAbstractsImports = require('vue-cli-plugin-bootstrap-vue/sassAbstractsImports.js')
module.exports = {
	css: {
		loaderOptions: {
		}
	},
	devServer: {
		public : "http://localhost:8080"
	},
	pwa: {
		workboxPluginMode: "GenerateSW",
		name: "Alli-Punkte",
		themeColor: "#FFFFFF",
		appleMobileWebAppCapable: 'yes',
		appleMobileWebAppStatusBarStyle: 'black',

	}
}