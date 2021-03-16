/**
 * @module      MysteryCode/Core/Acp/Package/BetterServersUpdateSearch
 */
define(['Language', 'Ajax', 'Ui/Dialog'], function (Language, Ajax, UiDialog) {
	function BetterServersUpdateSearch(bindOnExistingButtons) {
		this.init(bindOnExistingButtons);
	}
	
	BetterServersUpdateSearch.prototype = {
		/** @var {Element} */
		_button: null,
		
		/**
		 * dialog overlay
		 * @var	jQuery
		 */
		_dialog: null,
		
		init: function(bindOnExistingButtons) {
			if (!bindOnExistingButtons === true) {
				let li = elCreate('li');
				li.innerHTML = '<a href="#" class="button jsButtonSearchForUpdates"><span class="icon icon16 fa-refresh"></span> <span>' + Language.get('wcf.acp.package.searchForUpdates') + '</span></a>';
				elBySel('.contentHeaderNavigation > ul').prepend(li);
			}
			
			this._button = elBySel('.jsButtonSearchForUpdates');
			if (this._button) this._button.addEventListener(WCF_CLICK_EVENT, this._click.bind(this));
		},
		
		_click: function(event) {
			event.preventDefault();
			
			if (this._button.classList.contains('disabled')) {
				return;
			}
			
			this._button.classList.add('disabled');
			
			if (this._dialog === null) {
				Ajax.api(this);
			}
			else {
				this._dialog.wcfDialog('open');
			}
		},
		
		_ajaxSetup: function() {
			return {
				data: {
					actionName: 'searchForUpdates',
					className: 'wcf\\data\\package\\update\\PackageUpdateAction',
					parameters: {
						ignoreCache: 1
					}
				}
			}
		},
		
		_ajaxSuccess: function(data) {
			if (typeof window._trackSearchForUpdates === 'function') {
				window._trackSearchForUpdates(data);
				return;
			}
			
			if (data.returnValues.url) {
				window.location = data.returnValues.url;
			}
			else {
				this._dialog = UiDialog.open(this);
				this._button.classList.remove('disabled');
			}
		},
		
		_dialogSetup: function() {
			return {
				'id': 'searchForUpdates',
				'title': Language.get('wcf.acp.package.searchForUpdates'),
				'source': Language.get('wcf.acp.package.searchForUpdates.noResults')
			};
		}
	}
	
	return BetterServersUpdateSearch;
});
