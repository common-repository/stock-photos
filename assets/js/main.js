var Themely = {};

Themely.StockPhotos = (function ($) {

    var _self,
        _unsplash,
        _searching = false,
        _searchTerm = '',
        _currentTab = '',
        _selectedImages = [],
        _selectionDisabled = false,
        _settings = {       /*  DEFAULTS  */
            text: {
                noResults: "Nothing found for this search term",
                error: "Something bad has happened, try again."
            },
            resultContainer: '.stockPhoto_results', //
            providerContainer: '.tabResults',
            nextButton: '.next',  //
            prevButton: '.prev',  //
            resultsPerPage: 20,
            searchBox: '.searchStockPhotos', //Single BOX
            tabClass: '.tab',         //tab button...
            activeTab: 'unsplash',
            searchButton: '.doSearch',
            pexelsEnabled: true,
            unsplashEnabled: true,
            pixabayEnabled: true,
            pexelApiKey: "",
            unsplashApiKey: "",
            pixabayApiKey: "",
            selectedClickHandler: function (e, imageData) {
                alert(imageData.url + '<br/>' + imageData.provider);
            },
            disselectedClickHandler: function (e, imageData) {
                alert(imageData.url + '<br/>' + imageData.provider);
            }
        };


    var providerState = {
        pexels: {
            result_count: 0,
            current_page: 1,
            searching: false,
            lastTerm: '',
            error: ''
        },
        unsplash: {
            result_count: 0,
            current_page: 1,
            searching: false,
            lastTerm: '',
            error: ''
        },
        pixabay: {
            result_count: 0,
            current_page: 1,
            searching: false,
            lastTerm: '',
            error: ''
        }
    };

    return {

        disableSelection: function(){
            _selectionDisabled = true;
        },
        selectedImages: function(){
          return _selectedImages;
        },
        clearSelectedImages: function(){
            _selectedImages = [];
            _selectionDisabled = false;
        },
        init: function (settings) {

            _self = this;

            $.extend(_settings, settings);

            //Show/Hide Tabs...
            _self.toggleTabPages(_settings.activeTab);

            if (!_settings.pexelsEnabled) {
                $(_settings.tabClass + '[data-provider=pexels]').hide();

            }

            if (!_settings.pixabayEnabled) {
                $(_settings.tabClass + '[data-provider=pixabay]').hide();

            }

            if (!_settings.unsplashEnabled) {
                $(_settings.tabClass + '[data-provider=unsplash]').hide();
            } else {
                _unsplash = new Unsplash.default({
                    applicationId: _settings.unsplashApiKey,
                    secret: "12345",
                    callbackUrl: ""
                });
            }

            $(_settings.tabClass).click(function (e) {
                e.preventDefault();
                var provider = $(this).attr('data-provider');
                _self.activateTab(provider);
            });

            $(_settings.nextButton).click(function (e) {
                e.preventDefault();
                var provider = $(this).parent().parent().attr('id');
                providerState[provider].current_page = providerState[provider].current_page + 1;
                _self.doSearch(provider);
            });

            $(_settings.prevButton).click(function (e) {
                e.preventDefault();
                var provider = $(this).parent().parent().attr('id');
                providerState[provider].current_page = providerState[provider].current_page - 1;
                _self.doSearch(provider);

            });

            $(_settings.searchButton).click(function (e) {
                e.preventDefault();

                if (_searching) return;

                _searchTerm = $(_settings.searchBox).val();
                _self.resetProviders(false);
                _self.doSearch(_currentTab);
            });


            _self.activateTab(_settings.activeTab);

        },
        toggleTabPages: function (active) {

            $(_settings.providerContainer).hide(); //Hide All

            if (typeof active !== 'undefined') {
                $('#' + active).show();
            }

        },
        activateTab: function (provider) {

            _self.toggleTabPages(provider);

            var tab = $(_settings.tabClass + '[data-provider=' + provider + ']');
            $(_settings.tabClass).removeClass('active');
            $(tab).addClass('active');
            _currentTab = provider;

            if (providerState[provider].lastTerm != _searchTerm) {
                _self.doSearch(provider); //Bring up the results for this term on this tab.
            }

        },

        map_pexels: function (results) {


            return results.map(function (result) {
                return {
                    thumb: result.urls.thumb,
                    fullsize: result.urls.raw,
                    preview: result.urls.raw,
                    author: result.user.name,
                    description: '',
                    title: '',
                    date: result.dateCreated
                };
            });


        },
        map_pixabay: function (results) {

            providerState.pixabay.result_count = results.totalHits;

            return results.hits.map(function (result) {
                return {
                    thumb: result.previewURL,
                    fullsize: result.webformatURL.replace('_640', '_960'),  //Sort of full size...
                    preview: result.webformatURL.replace('_640', '_960'),
                    author: result.user,
                    description: result.tags,
                    title: result.type,
                    date: result.dateCreated
                };
            });

        },

        map_unsplash: function (results) {
            return results.map(function (result) {
                return {
                    thumb: result.urls.thumb,
                    fullsize: result.urls.regular.replace('&w=1080','&w=1600'),
                    preview: result.urls.regular,
                    author: result.user.name,
                    description: '',
                    title: '',
                    date: result.dateCreated
                };
            });
        },

        renderResults: function (provider, results) {

            var container = $('#' + provider + ' .stockPhoto_results');
            var htmlResults = "";
            var mappedResult = _self['map_' + provider](results);

            this.hideLoading(provider);

            if (mappedResult.length > 0) {
                htmlResults = "<ul>";
                for (var result in mappedResult) {
                    htmlResults += "<li class='" + provider + "_image' data-provider='" + provider + " data-author='" + mappedResult[result].author + "data-description='" + mappedResult[result].description + "' data-title='" + mappedResult[result].title + "' data-date='" + mappedResult[result].date + "' data-url='" + mappedResult[result].fullsize + "'>" +
                        "<button type='button' class='button-link check' tabindex='0'><span class='media-modal-icon'></span><span class='screen-reader-text'>Select</span></button>" +
                        "<a href='" + mappedResult[result].preview + "' class='button-link zoom' tabindex='0' target='_blank'><span class='media-zoom-icon'></span><span class='screen-reader-text'>Select</span></a>" +
                        "<div class='thumb_container'><img src='" + mappedResult[result].thumb + "' title='" + mappedResult[result].title + "'/></div></li>";
                }

                htmlResults += "</ul>"
            } else {
                htmlResults = "<p>" + _settings.text.noResults + "</p>";
            }

            $(container).html(htmlResults);

            if (results.length < _settings.resultsPerPage)
                $('#' + provider + ' ' + _settings.nextButton).hide();
            else
                $('#' + provider + ' ' + _settings.nextButton).show();


            if (providerState[provider].current_page > 1)
                $('#' + provider + ' ' + _settings.prevButton).show();
            else
                $('#' + provider + ' ' + _settings.prevButton).hide();


            _self.addImageHandlers(provider);

        },

        showLoading: function (provider) {
            $('#' + provider + ' ' + _settings.resultContainer).html('');
            $('#' + provider + ' .stockPhoto_results').addClass('tlsp_loading');
        },

        hideLoading: function (provider) {
            $('#' + provider + ' .stockPhoto_results').removeClass('tlsp_loading');
        },


        addImageHandlers: function (provider) {

            var insertImage = $('#' + provider + ' .stockPhoto_results li .thumb_container');

            if ($(insertImage).length > 0) {
                $(insertImage).click(function (e) {

                    if(_selectionDisabled)
                        return false;

                    var imageData;

                    var el = $(this).parent();

                    $(el).toggleClass('selected');

                    if ($(el).hasClass('selected')) {

                        if (typeof _settings.selectedClickHandler === 'function') {

                            imageData = {
                                image: $(el).attr('data-url'),
                                author: $(el).attr('data-author'),
                                title: $(el).attr('data-title'),
                                description: $(el).attr('data-description'),
                                query: _searchTerm,
                                provider: provider
                            };

                            _selectedImages.push(imageData);
                            e.preventDefault();
                            _settings.selectedClickHandler(e, imageData);
                        }
                    }else{
                        if (typeof _settings.disselectedClickHandler === 'function') {

                            imageData = {
                                image: $(el).attr('data-url'),
                                author: $(el).attr('data-author'),
                                title: $(el).attr('data-title'),
                                description: $(el).attr('data-description'),
                                query: _searchTerm,
                                provider: provider
                            };

                            _selectedImages =  jQuery.grep(_selectedImages, function(e){
                                return e.image != imageData.image;
                            });

                            e.preventDefault();

                            _settings.disselectedClickHandler(e, imageData);
                        }
                    }
                });
            }
        },
        resetProviders: function (resetCache = false) {
            for (var p in providerState) {
                providerState[p].current_page = 1;
                providerState[p].result_count = 0;
                if (resetCache) providerState[p].cache = {};
                $('#' + p + ' ' + _settings.resultContainer).html('');
            }
        },
        //Search
        doSearch: function (provider) {

            var current_page = providerState[provider].current_page;

            if (_searchTerm.trim() == '')
                return;

            _searching = true;
            this.showLoading(provider);
            providerState[provider].lastTerm = _searchTerm;

            switch (provider) {
                case 'unsplash':

                    if (typeof _unsplash == 'undefined')
                        console.log("provider " + provider + " Not initialized or enabled");


                    _unsplash.photos.searchPhotos(_searchTerm, [], current_page, _settings.resultsPerPage)
                        .then(Unsplash.toJson, function (error) {
                            //oops something bad happened?
                            providerState.unsplash.error = error;
                            _self.hideLoading(provider);
                            _searching = false;
                        })
                        .then(function (result) {
                            _self.renderResults(provider, result);
                            _searching = false;
                        });

                    break;

                case 'pexels':

                    var pexelURL = 'http://api.pexels.com/v1/search?query=' + encodeURIComponent(_searchTerm) + '&per_page=' + _settings.resultsPerPage + '&page=' + current_page;

                    $.ajaxSetup({
                        headers: {
                            'Authorization': _settings.pexelApiKey
                        }
                    });

                    $.getJSON(pexelURL, function (result) {
                        _self.renderResults(provider, result);
                    }).fail(function () {
                        _self.hideLoading(provider);
                        providerState.pexels.error = "Api Error/Authorization error";

                    }).always(function () {
                        _searching = false;
                        delete $.ajaxSettings.headers["Authorization"];
                    });

                    break;

                case 'pixabay':

                    var pixabayURL = "https://pixabay.com/api/?key=" + _settings.pixabayApiKey + "&q=" + encodeURIComponent(_searchTerm) + "&page=" + current_page + "&per_page=" + _settings.resultsPerPage;

                    $.getJSON(pixabayURL, function (result) {
                        _self.renderResults(provider, result);
                    }).fail(function () {
                        providerState.pixabay.error = "Api Error/Authorization error";

                        _self.hideLoading(provider);
                    }).always(function () {
                        _searching = false;
                    });

                    break;

                default:
                    _searching = false;
                    console.log("Error, invalid/non-existent provider");

            }
        }
    };

}(jQuery));