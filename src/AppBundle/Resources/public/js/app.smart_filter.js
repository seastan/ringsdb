(function(smart_filter, $) {

    var SmartFilterQuery = [];

    var configuration = {
        b: [add_integer_sf, 'threat', "Threat"],
        o: [add_string_sf,  'cost', "Cost"],
        a: [add_integer_sf, 'attack', "Attack"],
        d: [add_integer_sf, 'defense', "Defense"],
        w: [add_integer_sf, 'willpower', "Willpower"],
        h: [add_integer_sf, 'health', "Hit Points"],
        e: [add_string_sf,  'pack_code', "Adventure Pack code"],
        c: [add_string_sf,  'cycle_code', "Cycle code"],
        t: [add_string_sf,  'type_code', "Type"],
        s: [add_string_sf,  'sphere_code', "Sphere"],
        u: [add_boolean_sf, 'is_unique', "Uniqueness"],
        k: [add_text_sf,    's_traits', "Traits"],
        x: [add_text_sf,    's_text', "Text"],
        y: [add_integer_sf, 'quantity', "Quantity in pack"],
        f: [add_text_sf,    's_flavor', "Flavor text"],
        i: [add_text_sf,    'illustrator', "Illustrator"],
        z: [add_boolean_sf, 'has_errata', "Errata'd"]
    };

    /**
     * called when the list is refreshed
     * @memberOf smart_filter
     */
    smart_filter.get_query = function(query) {
        return _.extend(query, SmartFilterQuery);
    };

    /**
     * called when the filter input is modified
     * @memberOf smart_filter
     */
    smart_filter.update = function(value) {
        var conditions = filterSyntax(value);
        SmartFilterQuery = {};

        for (var i = 0; i < conditions.length; i++) {
            var condition = conditions[i];
            var type = condition.shift();
            var operator = condition.shift();
            var values = condition;

            var tools = configuration[type];
            if (tools) {
                tools[0].call(this, tools[1], operator, values);
            }
        }
    };

    smart_filter.get_help = function() {
        var items = _.map(configuration, function(value, key) {
            return '<li><code>' + key + '</code> &ndash; ' + value[2] + '</li>';
        });
        return '<ul>' + items.join('') + '</ul><p>Example: <code>o:2 w>3</code> shows all cards with Cost 2 and Willpower greater than 3</p>';
    };

    function add_integer_sf(key, operator, values) {
        for (var j = 0; j < values.length; j++) {
            values[j] = parseInt(values[j], 10);
        }
        switch (operator) {
            case ":":
                SmartFilterQuery[key] = {
                    '$in': values
                };
                break;
            case "<":
                SmartFilterQuery[key] = {
                    '$lt': values[0]
                };
                break;
            case ">":
                SmartFilterQuery[key] = {
                    '$gt': values[0]
                };
                break;
            case "!":
                SmartFilterQuery[key] = {
                    '$nin': values
                };
                break;
        }
    }

    function add_string_sf(key, operator, values) {
        for (var j = 0; j < values.length; j++) {
            values[j] = new RegExp('(?<=^|-| )' + app.data.get_searchable_string(values[j]), 'i');
        }
        switch (operator) {
            case ":":
                SmartFilterQuery[key] = {
                    '$in': values
                };
                break;
            case "!":
                SmartFilterQuery[key] = {
                    '$nin': values
                };
                break;
        }
    }

    function add_text_sf(key, operator, values) {
        for (var j = 0; j < values.length; j++) {
            values[j] = new RegExp(app.data.get_searchable_string(values[j]), 'i');
        }
        switch (operator) {
            case ":":
                SmartFilterQuery[key] = {
                    '$in': values
                };
                break;
            case "!":
                SmartFilterQuery[key] = {
                    '$nin': values
                };
                break;
        }
    }

    function add_boolean_sf(key, operator, values) {
        var value = parseInt(values.shift()), target = !!value;
        switch (operator) {
            case ":":
                SmartFilterQuery[key] = target;
                break;
            case "!":
                SmartFilterQuery[key] = {
                    '$ne': target
                };
                break;
        }
    }

    function filterSyntax(query) {
        // renvoie une liste de conditions (array)
        // chaque condition est un tableau à n>1 éléments
        // le premier est le type de condition (0 ou 1 caractère)
        // les suivants sont les arguments, en OR

        query = query.replace(/^\s*(.*?)\s*$/, "$1").replace('/\s+/', ' ');
        query = query.replace('t:campaign', 't:treasure').replace('t:campaig', 't:treasure').replace('t:campai', 't:treasure').replace('t:campa', 't:treasure').replace('t:camp', 't:treasure').replace('t:cam', 't:treasure').replace('t:ca', 't:treasure');
        query = query.replace('t:other', 't:contract').replace('t:othe', 't:contract').replace('t:oth', 't:contract').replace('t:ot', 't:contract').replace('t:o', 't:contract');

        var list = [];
        var cond = null;
        // l'automate a 3 états :
        // 1:recherche de type
        // 2:recherche d'argument principal
        // 3:recherche d'argument supplémentaire
        // 4:erreur de parsing, on recherche la prochaine condition
        // s'il tombe sur un argument alors qu'il est en recherche de type, alors le
        // type est vide
        var etat = 1;
        while (query != "") {
            if (etat == 1) {
                if (cond !== null && etat !== 4 && cond.length > 2) {
                    list.push(cond);
                }
                // on commence par rechercher un type de condition
                if (query.match(/^(\w)([:<>!])(.*)/)) { // jeton "condition:"
                    cond = [RegExp.$1.toLowerCase(), RegExp.$2];
                    query = RegExp.$3;
                } else {
                    cond = ["", ":"];
                }
                etat = 2;
            } else {
                if (query.match(/^"([^"]*)"(.*)/) // jeton "texte libre entre guillements"
                    || query.match(/^([^\s]+)(.*)/) // jeton "texte autorisé sans guillements"
                ) {
                    if ((etat === 2 && cond.length === 2) || etat === 3) {
                        cond.push(RegExp.$1);
                        query = RegExp.$2;
                        etat = 2;
                    } else {
                        // erreur
                        query = RegExp.$2;
                        etat = 4;
                    }
                } else if (query.match(/^\|(.*)/)) { // jeton "|"
                    if ((cond[1] === ':' || cond[1] === '!')
                        && ((etat === 2 && cond.length > 2) || etat === 3)) {
                        query = RegExp.$1;
                        etat = 3;
                    } else {
                        // erreur
                        query = RegExp.$1;
                        etat = 4;
                    }
                } else if (query.match(/^ (.*)/)) { // jeton " "
                    query = RegExp.$1;
                    etat = 1;
                } else {
                    // erreur
                    query = query.substr(1);
                    etat = 4;
                }
            }
        }
        if (cond !== null && etat !== 4 && cond.length > 2) {
            list.push(cond);
        }
        return list;
    }

    $(function() {
        $('.smart-filter-help').tooltip({
            container: 'body',
            delay: 200,
            html: true,
            placement: 'bottom',
            title: smart_filter.get_help(),
            trigger: 'hover'
        });
    })

})(app.smart_filter = {}, jQuery);
