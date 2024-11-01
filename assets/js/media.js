var Themely = {};

Themely.ReloadMediaLibrary = function(){

    jQuery('.media-menu-item:first-child').click(); //Change to media tab

    setTimeout(function(){

        var content = wp.media.frame.content;

        if(content.get()!==null){
            var get = content.get();
            get.collection.props.set({ignore: (+ new Date())});
            get.options.selection.reset();
        }else{
            wp.media.frame.library.props.set({ignore: (+ new Date())});
        }

        jQuery("span.spinner").show();

    },10);

};
