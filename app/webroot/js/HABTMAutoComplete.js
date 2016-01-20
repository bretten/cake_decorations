/**
 * jQuery script to assist in HABTM-related form functionality.
 *
 * @author https://github.com/bretten
 */
var HABTMAutoComplete = (function () {

    /**
     * Namespace
     *
     * @type {{}}
     */
    var ns = {};

    /**
     * Represents all the potential records that the current Model could HABTM.
     *
     * Contains object in the format:
     *     {
     *         id: id,
     *         value: value,
     *         label: label
     *     }
     *
     * @type {Array}
     */
    ns.all = new Array();

    /**
     * Represents the current HABTM records.
     *
     * Contains object in the format:
     *     {
     *         id: id,
     *         name: name
     *     }
     *
     * @type {Array}
     */
    ns.current = new Array();

    /**
     * Represents the an entity not found in the stored HABTM records.
     *
     * Can be used to add new HABTM row.
     *
     * Contains object in the format:
     *     {
     *         id: id,
     *         value: value
     *     }
     *
     * @type {Array}
     */
    ns.new = new Array();

    /**
     * Contains selectors used for the various elements.
     *
     * Requires the following be set:
     *     selector['container']
     *     selector['autocomplete']
     *     selector['form']
     *     selector['hidden_options']
     *     selector['remove']
     *
     * @type {Array}
     */
    ns.selectors = new Array();

    /**
     * The attribute with the value as the id that will be attached to elements that are used to delete a HABTM from the internal "current" array.
     *
     * @type string
     */
    ns.deleteAttr = "data-habtm-id";

    /**
     * String to prefix new HABTM record id's with.
     *
     * @type string
     */
    ns.newPrefix = "new_";

    /**
     * Markup generation function.
     *
     * @type function
     */
    ns.generateMarkup = function (id, value) {
        return '<li>' + value + '<button type="button" class="' + ns.selectors['remove'] + '" ' + ns.deleteAttr + '="' + id + '">x</button></li>';
    };

    /**
     * Constructor
     *
     * @param params Object containing setup parameters.
     */
    var construct = function (params) {
        ns.all = params.all;
        ns.current = params.current;
        ns.deleteAttr = params.deleteAttr;
        ns.selectors = params.selectors;
        var type = {};
        if (params.generateMarkup && type.toString.call(params.generateMarkup) === '[object Function]') {
            ns.generateMarkup = params.generateMarkup;
        }

        listeners();
    }

    /**
     * Sets up necessary listeners.
     */
    var listeners = function () {
        // Generates the initial markup
        for (var i = 0; i < ns.current.length; i++) {
            $(ns.selectors['container']).append(ns.generateMarkup(ns.current[i].id, ns.current[i].name));
        }

        // Autocomplete handler
        $(ns.selectors['autocomplete']).autocomplete({
            source: ns.all,
            select: function (event, ui) {
                if (exists(ui.item.id, ns.current, 'id') === -1) {
                    ns.current.push({
                        id: ui.item.id,
                        name: ui.item.value
                    });

                    // Append the markup to the container
                    $(ns.selectors['container']).append(ns.generateMarkup(ui.item.id, ui.item.value));

                    // Empty out the autocomplete input
                    $(this).val('');
                    return false;
                }
            },
            response: function (event, ui) {
                if (ui.content.length === 0) {
                    var value = $(this).val();

                    if (confirm("Add '" + value + "'?")) {
                        if (exists(value, ns.new, 'value') === -1) {
                            ns.new.push({
                                id: ns.newPrefix + event.timeStamp,
                                value: value
                            });

                            // Append the markup to the container
                            $(ns.selectors['container']).append(ns.generateMarkup(ns.newPrefix + event.timeStamp,
                                value));
                        }

                        $(this).val("");
                    }
                }
            }
        });

        // Listener for handling adding the exact value in the input regardless of whether an autocomplete value was found
        $(ns.selectors['add']).click(function (e) {
            var value = $(ns.selectors['autocomplete']).val();
            if ($.trim(value).length > 0) {
                // Check if it already exists
                if (exists(value, ns.current, 'name') >= 0 || exists(value, ns.new, 'value') >= 0) {
                    alert('Already exists.');

                    return false;
                }

                // Push it to the new objects internal array
                ns.new.push({
                    id: ns.newPrefix + e.timeStamp,
                    value: value
                });

                // Append the markup to the container
                $(ns.selectors['container']).append(ns.generateMarkup(ns.newPrefix + e.timeStamp, value));

                // Clear the autocomplete input
                $(ns.selectors['autocomplete']).val("");
            }
        });

        // Handles removal of a HABTM from the internal "current" array and removes the markup.
        $(document).on('click', ns.selectors['remove'], function () {
            var id = $(this).attr(ns.deleteAttr);
            var ref; // Reference to the array object
            // If the delete attribute is prefixed with the new indicator, it is a new object
            if (id.indexOf(ns.newPrefix) != -1) {
                ref = ns.new;
            } else {
                ref = ns.current;
            }
            // Remove it if it exists
            var i = exists(id, ref, 'id');
            if (i > -1) {
                ref.splice(i, 1);
            }
            // Remove the markup
            $(this).parent().remove();
        });

        // On a form submit, will take all the ids in the internal "current" array and selects the corresponding option in the hidden multiple option input
        $(ns.selectors['form']).submit(function (e) {
            // Select the corresponding options for the internal "current" array
            for (var i = 0; i < ns.current.length; i++) {
                $(ns.selectors['hidden_options'] + ' option[value="' + ns.current[i].id + '"]').prop('selected', true);
            }

            // Serialize the internal "new" array
            $(ns.selectors['hidden_new']).val(JSON.stringify(ns.new));
        });
    }

    /**
     * Checks if the needle (an object with an id) exists in the array.
     *
     * @param needle
     * @param haystack
     * @param property The property to check
     * @returns {number}
     */
    var exists = function (needle, haystack, property) {
        for (var i = 0; i < haystack.length; i++) {
            if (needle === haystack[i][property]) {
                return i;
            }
        }
        return -1;
    }

    return {
        construct: construct
    }

})();
