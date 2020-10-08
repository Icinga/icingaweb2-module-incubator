(function(window, $) {
    'use strict';

    var Incubator = function (icinga) {
        this.icinga = icinga;
    };

    Incubator.prototype = {
        initialize: function (icinga) {
        },

        destroy: function () {
            window.incubatorComponentLoader.destroy();
            this.icinga = null;
        }
    };

    Icinga.availableModules.incubator = Incubator;
})(window, jQuery);
