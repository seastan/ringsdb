(function app_format(format, $) {

	format.traits = function traits(card) {
		return card.traits || '';
	};

	format.name = function name(card) {
		return (card.is_unique ? '<span class="icon-unique"></span>' : '') + card.name;
	};

	format.type = function type(card) {
		return card.type_name + '. ';
	};

	format.sphere = function sphere(card) {
		return card.sphere_name + '. ';
	};

	format.pack = function pack(card) {
		return card.pack_name + ' #' + card.position + '. ';
	};

	format.pack_sphere = function pack_sphere(card) {
		return card.pack_name + ' #' + card.position + '. ' + card.sphere_name + '. ';
	};

	format.cost = function info(card) {
		switch (card.type_code) {
			case 'attachment':
			case 'event':
			case 'ally':
			case 'player-side-quest':
			case 'contract':
			case 'treasure':
				return 'Cost: ' + (card.cost != null ? card.cost : 'X') + '. ';

			case 'hero':
				return 'Threat: ' + (card.threat != null ? card.threat : 'X') + '. ';
		}

		return '';
	};

	format.type_cost = function type_cost(card) {
		return '<span class="card-type">' + format.type(card) + '</span>' + format.cost(card);
	};

	format.stats = function info(card) {
		var text = '';

		if (card.victory) {
			text += 'Victory ' + card.victory + '. ';
		}

		switch (card.type_code) {
			case 'hero':
			case 'ally':
				text += (card.willpower != null ? card.willpower : 'X') + ' <span class="icon icon-willpower" title="Willpower"></span>&#160; ';
				text += (card.attack != null ? card.attack : 'X') + ' <span class="icon icon-attack" title="Attack"></span>&#160; ';
				text += (card.defense != null ? card.defense : 'X') + ' <span class="icon icon-defense" title="Defense"></span>&#160; ';
				text += (card.health != null ? card.health : 'X') + ' <span class="icon icon-health" title="Hit Points"></span>&#160; ';
				break;
		}
		return text;
	};

	format.info = function info(card) {
		var text = '<span class="card-type">' + card.type_name + '. </span>';
		text += format.cost(card);
		text += format.stats(card);
		return text;
	};

	format.text = function text(card) {
		var text = card.text || '';
		text = text.replace(/\[(\w+)\]/g, '<span class="icon-$1"></span>');
		text = text.split("\n").join('</p><p>');
		return '<p>' + text + '</p>';
	};

})(app.format = {}, jQuery);
