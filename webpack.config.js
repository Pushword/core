const EncoreHelper = require("@pushword/js-helper/src/encore.js");

var watchFiles = [
    "./src/templates/**/*.html.twig",
    "./src/templates/*.html.twig",
    "./../conversation/src/templates/conversation/*.html.twig",
    "./../admin-block-editor/src/templates/block/*.html.twig",
];

var tailwindConfig = EncoreHelper.getTailwindConfig(watchFiles);

module.exports = EncoreHelper.getEncore(
    watchFiles,
    tailwindConfig,
    "./src/Resources/public/",
    "./",
    "bundles/pushwordcore",
    [
        {
            from: "./src/Resources/assets/favicons",
            to: "favicons/[name].[ext]",
        },
    ],
    [
        { name: "app", file: "./src/Resources/assets/page.js" }, // {{ asset('bundles/pushwordcore/page.min.js') }}
    ],
    [{ name: "style", file: "./src/Resources/assets/tailwind.css" }]
).getWebpackConfig();
