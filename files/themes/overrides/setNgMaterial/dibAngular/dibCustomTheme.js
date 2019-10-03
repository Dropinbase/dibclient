(function () {
    var dibCustomTheme = angular.module('dibCustomTheme', ['ngMaterial']);
    customThemeDefinition.$inject = ['$mdThemingProvider'];
    dibCustomTheme.config(customThemeDefinition);

    function customThemeDefinition($mdThemingProvider) {
        $mdThemingProvider.definePalette('customPrimary', {
            '50': 'edf5ea',
            '100': 'd2e5ca',
            '200': 'b5d4a7',
            '300': '97c284',
            '400': '80b569',
            '500': '6aa84f',
            '600': '62a048',
            '700': '57973f',
            '800': '4d8d36',
            '900': '3c7d26',
            'A100': 'cdffbe',
            'A200': 'a6ff8b',
            'A400': '7fff58',
            'A700': '6cff3f',
            'contrastDefaultColor': 'light',
            'contrastDarkColors': [
                '50',
                '100',
                '200',
                '300',
                '400',
                '500',
                '600',
                'A100',
                'A200',
                'A400',
                'A700'
            ],
            'contrastLightColors': [
                '700',
                '800',
                '900'
            ]
        });
        // Use this to set the theme shades
        // DIB.primary_shades = {
        //     'default': '400', // by default use shade 400 from the pink palette for primary intentions
        //         'hue-1': '100', // use shade 100 for the <code>md-hue-1</code> class
        //         'hue-2': '600', // use shade 600 for the <code>md-hue-2</code> class
        //         'hue-3': 'A100'
        // };
        $mdThemingProvider.definePalette('customAccent', {
            '50': 'fdf8e6',
            '100': 'fbedc2',
            '200': 'f8e199',
            '300': 'f5d470',
            '400': 'f3cb51',
            '500': 'f1c232',
            '600': 'efbc2d',
            '700': 'edb426',
            '800': 'ebac1f',
            '900': 'e79f13',
            'A100': 'ffffff',
            'A200': 'fff4e2',
            'A400': 'ffe2af',
            'A700': 'ffd896',
            'contrastDefaultColor': 'light',
            'contrastDarkColors': [
                '50',
                '100',
                '200',
                '300',
                '400',
                '500',
                '600',
                '700',
                '800',
                '900',
                'A100',
                'A200',
                'A400',
                'A700'
            ],
            'contrastLightColors': []
        });
        // Use this to set the theme shades
        // DIB.accent_shades = {
        //     'default': '400', // by default use shade 400 from the pink palette for primary intentions
        //         'hue-1': '100', // use shade 100 for the <code>md-hue-1</code> class
        //         'hue-2': '600', // use shade 600 for the <code>md-hue-2</code> class
        //         'hue-3': 'A100'
        // };
        $mdThemingProvider.definePalette('customWarn', {
            '50': 'fdefe6',
            '100': 'fad7c1',
            '200': 'f7bd97',
            '300': 'f3a36d',
            '400': 'f18f4e',
            '500': 'ee7b2f',
            '600': 'ec732a',
            '700': 'e96823',
            '800': 'e75e1d',
            '900': 'e24b12',
            'A100': 'ffffff',
            'A200': 'ffe5dd',
            'A400': 'ffbeaa',
            'A700': 'ffab90',
            'contrastDefaultColor': 'light',
            'contrastDarkColors': [
                '50',
                '100',
                '200',
                '300',
                '400',
                '500',
                '600',
                '700',
                'A100',
                'A200',
                'A400',
                'A700'
            ],
            'contrastLightColors': [
                '800',
                '900'
            ]
        });
        // Use this to set the theme shades
        // DIB.warn_shades = {
        //     'default': '400', // by default use shade 400 from the pink palette for primary intentions
        //         'hue-1': '100', // use shade 100 for the <code>md-hue-1</code> class
        //         'hue-2': '600', // use shade 600 for the <code>md-hue-2</code> class
        //         'hue-3': 'A100'
        // };

        $mdThemingProvider.definePalette('customBackground', {
            '50': 'f9fbfc',
            '100': 'f1f6f7',
            '200': 'e8f0f1',
            '300': 'dee9eb',
            '400': 'd7e5e7',
            '500': 'd0e0e3',
            '600': 'cbdce0',
            '700': 'c4d8dc',
            '800': 'bed3d8',
            '900': 'b3cbd0',
            'A100': 'ffffff',
            'A200': 'ffffff',
            'A400': 'ffffff',
            'A700': 'ffffff',
            'contrastDefaultColor': 'light',
            'contrastDarkColors': [
                '50',
                '100',
                '200',
                '300',
                '400',
                '500',
                '600',
                '700',
                '800',
                '900',
                'A100',
                'A200',
                'A400',
                'A700'
            ],
            'contrastLightColors': []
        });
        // Use this to set the theme shades
        // DIB.background_shades = {
        //     'default': '400', // by default use shade 400 from the pink palette for primary intentions
        //         'hue-1': '100', // use shade 100 for the <code>md-hue-1</code> class
        //         'hue-2': '600', // use shade 600 for the <code>md-hue-2</code> class
        //         'hue-3': 'A100'
        // };
    }

})();
