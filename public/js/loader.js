(function(window, $) {
    'use strict';

    var IncubatorComponentLoader = function () {
        this.components = [];
    };

    IncubatorComponentLoader.prototype = {
        initialize: function (icinga) {
            this.icinga = icinga;
            // Trigger module loading - always
            icinga.module('incubator');
            $.each(this.components, function (_, component) {
                component.initialize(icinga);
            });
            icinga.logger.info('Incubator is ready');
        },

        addComponent: function (component) {
            this.components.push(component);
        },

        destroy: function () {
            // Eventually: this.unbindEventHandlers();

            $.each(this.components, function (_, component) {
                if (typeof component.destroy === 'function') {
                    component.destroy();
                }
            });

            this.components = [];
            this.icinga = null;
        }
    };

    var startup;
    var w = window;
    function safeLaunch()
    {
        if (typeof(w.icinga) !== 'undefined' && w.icinga.initialized) {
            clearInterval(startup);
            w.incubatorComponentLoader.initialize(w.icinga);
        } else {
            console.log('Incubator module is still waiting for icinga');
        }
    }

    $(document).ready(function () {
        startup = setInterval(safeLaunch, 30);
        safeLaunch();
    });
    w.incubatorComponentLoader = new IncubatorComponentLoader();
})(window, jQuery);
