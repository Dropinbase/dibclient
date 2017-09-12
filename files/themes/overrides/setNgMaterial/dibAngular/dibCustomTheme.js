(function () {
    var dibCustomTheme = angular.module('dibCustomTheme', ['ngMaterial']);
    customThemeDefinition.$inject = ['$mdThemingProvider'];
    dibCustomTheme.config(customThemeDefinition);

    function customThemeDefinition($mdThemingProvider) {
        $mdThemingProvider.definePalette('customPrimary', {
            '50': 'e1e9eb',
            '100': 'b4c7ce',
            '200': '82a2ad',
            '300': '4f7d8c',
            '400': '2a6174',
            '500': '04455b',
            '600': '033e53',
            '700': '033649',
            '800': '022e40',
            '900': '011f2f',
            'A100': '68bdff',
            'A200': '35a6ff',
            'A400': '0290ff',
            'A700': '0082e7',
            'contrastDefaultColor': 'light',
            'contrastDarkColors': [
                '50',
                '100',
                '200',
                'A100',
                'A200'
            ],
            'contrastLightColors': [
                '300',
                '400',
                '500',
                '600',
                '700',
                '800',
                '900',
                'A400',
                'A700'
            ]
        });

        $mdThemingProvider.definePalette('customAccent', {
            '50': 'e1e9eb',
            '100': 'b4c7ce',
            '200': '82a2ad',
            '300': '4f7d8c',
            '400': '2a6174',
            '500': '04455b',
            '600': '033e53',
            '700': '033649',
            '800': '022e40',
            '900': '011f2f',
            'A100': '68bdff',
            'A200': '35a6ff',
            'A400': '0290ff',
            'A700': '0082e7',
            'contrastDefaultColor': 'light',
            'contrastDarkColors': [
                '50',
                '100',
                '200',
                'A100',
                'A200'
            ],
            'contrastLightColors': [
                '300',
                '400',
                '500',
                '600',
                '700',
                '800',
                '900',
                'A400',
                'A700'
            ]
        });

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
    }

})();
