<?php

$foo = '<p>foo1</p><p>bar1</p>
<module class="mceNonEditable" data-label="Foobar: Yessa">{"title2":"Foobar","amount":"10","checkbox-1":true,"colorinput":"#169179","title":"Yessa","SelectA":"two"}</module>
<p>foo2</p><p>bar2</p>
<module class="mceNonEditable" data-label="Foobar: Yessa">{"title2":"Foobar","amount":"10","checkbox-1":true,"colorinput":"#169179","title":"Yessa","SelectA":"two"}</module>
<p>foo3</p><p>bar3</p>';


/*echo json_encode([
'module' => 'news-listing',
'items'=>"6",
'headline' => 'Testheadline mit ein 𐩠 Wort in "Anführungszeichen"',
'pagination'=>"0",
'view'=>"small",
'parent_id'=>"42"
]);*/

?><!doctype html>
<html lang="de">
	<head>
		<meta charset="UTF-8">
		<meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
		<meta http-equiv="X-UA-Compatible" content="ie=edge">
		<title>Document</title>
		<script src="//cdnjs.cloudflare.com/ajax/libs/tinymce/5.6.2/tinymce.min.js"></script>
	</head>
	<body>
		<form method="post">
		<textarea id="test" name="foobar"><?=htmlspecialchars($foo, ENT_QUOTES, 'UTF-8', FALSE)?></textarea>
		<button type="submit">submit</button>
		</form>
		
		<!--&lt;module&gt;{ "book": { "name": "Harry Potter and the Goblet of Fire", "author": "J. K. Rowling", "year": 2000, "genre": "Fantasy Fiction",
		"bestseller": true } }&lt;/module&gt;-->

		<?php

		if ( ! empty($_POST['foobar'])) {
			echo '<code>';
			echo nl2br(htmlentities($_POST['foobar'], ENT_QUOTES, 'UTF-8', FALSE));
			echo '</code>';
		}

		?>
		
		<script>
			tinymce.PluginManager.add('example', function(editor, url) {
				var openDialog = function() {
					return editor.windowManager.open({
						title: 'Example plugin',
						body: {
							type: 'panel',
							items: [
								{
									type: 'bar', // component type
									items: [
										{
											type: 'input',
											name: 'title2',
											label: 'Title'
										},
										{
											type: 'alertbanner', // component type
											level: 'error',
											text: 'An <strong>informative</strong> message to the user',
											url: 'http://my.url',
											icon: 'question'
										},
										{
											type: 'input',
											name: 'amount',
											subtype: 'number',
											label: 'Amount',
											inputMode: 'numeric'
										}
									]
								},
								{
									type: 'bar', // component type
									items: [
										{
											type: 'checkbox', // component type
											name: 'checkbox-1', // identifier
											label: 'Irgendwas mit Checkbox', // text for the label
										},
										{
											type: 'colorinput', // component type
											name: 'colorinput', // identifier
											label: 'Color Label' // text for the label
										}
									]
								},
								{
									type: 'input',
									name: 'title',
									label: 'Title'
								},
								{
									type: 'selectbox', // component type
									name: 'SelectA', // identifier
									label: 'Dropdown',
									size: 1, // number of visible values (optional)
									items: [
										{ value: '', text: '' },
										{ value: 'one', text: 'One' },
										{ value: 'two', text: 'Two' }
									]
								}

							]
						},
						buttons: [
							{
								type: 'cancel',
								text: 'Close'
							},
							{
								type: 'submit',
								text: 'Save',
								primary: true
							}
						],
						onSubmit: function(api) {
							var data = api.getData();

							editor.insertContent('<module class="mceNonEditable" data-label="Foobar: ' + data.title + '">' + JSON.stringify(data) + '</module>');
							api.close();
						}
					});
				};

				/* Add a button that opens a window */
				editor.ui.registry.addButton('example', {
					text: 'My button',
					disabled: true,
					onAction: function() {
						/* Open window */
						var lo_windowManager = openDialog();
						var lo_node = editor.selection.getNode();
						if (lo_node.nodeName.toLocaleLowerCase() === 'module') {
							lo_windowManager.setData(JSON.parse(lo_node.textContent));
						}
					},
					onSetup: function (buttonApi) {
						editor.on('NodeChange', function (eventApi) {
							buttonApi.setDisabled(eventApi.element.nodeName.toLowerCase() !== 'module');
						});
					}
				});

				editor.on('copy', function(e) {
					if (window.moduleCopyCut(e, this)) {
						return false;
					}
				}.bind(editor));

				editor.on('cut', function(e) {
					if (window.moduleCopyCut(e, this)) {
						this.execCommand('Delete');
						return false;
					}
				}.bind(editor));

				editor.on('PastePreProcess', function(e) {
					e.content = e.content.replace(/&lt;module(.*?)&lt;\/module&gt;/g, function(match) {
						var txt = document.createElement('textarea');
						txt.innerHTML = match;
						return txt.value;
					});
				}.bind(editor));

				editor.on('dblclick', function(e) {
					if (e.target.nodeName.toLowerCase() === 'module') {
						console.log(this.plugins.example.foo(e, this));
					}
				}.bind(editor));

				/*editor.addCommand("mceImageDialog", function(ui, val) {
					showDialog();
				});*/

				/* Return the metadata for the help plugin */
				return {
					getMetadata: function() {
						return {
							name: 'Example plugin',
							url: 'http://exampleplugindocsurl.com'
						};
					},
					foo: function  (e, editor) {
						/* Open window */
						var lo_windowManager = openDialog();
						var lo_node = editor.selection.getNode();
						if (lo_node.nodeName.toLocaleLowerCase() === 'module') {
							lo_windowManager.setData(JSON.parse(lo_node.textContent));
						}
					}
				};
			});

			window.moduleCopyCut = function(e, editor) {
				var lb_foundModule = false;
				var li_i = 0, la_blocks = editor.selection.getSelectedBlocks();
				var ls_clipboardData = '';

				for (;li_i < la_blocks.length; li_i++) {
					var la_block = la_blocks[ li_i ];

					if (la_block.nodeName.toLowerCase() === 'module') {
						if (ls_clipboardData !== '') ls_clipboardData += "\n\n";
						ls_clipboardData += '<module class="' + la_block.attributes.class.nodeValue + '" data-label="' + la_block.dataset.label + '">' +
							la_block.textContent + '</module>';
						lb_foundModule = true;
					}
					else if (la_block.textContent !== '') {
						if (ls_clipboardData !== '') ls_clipboardData += "\n\n";
						ls_clipboardData += la_block.textContent;
					}
				}

				if (lb_foundModule) {
					e.clipboardData.setData('text/plain', ls_clipboardData);
					e.preventDefault();

					return true;
				}

				return false;
			};

			tinymce.init({
				selector: '#test',
				height: 400,
				plugins: 'example noneditable paste code',
				toolbar1: 'example code',
				menubar: false,
				object_resizing: false,
				paste_as_text: true,
				paste_block_drop: false,
				paste_data_images: true,
				custom_elements: 'module',
				extended_valid_elements: 'module[class]',
				content_css : 'css/foo.css',
				setup: function(editor) {

				}
			});
		</script>
	</body>
</html>