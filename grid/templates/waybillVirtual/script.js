$(document).ready(function(){
    $('body').on('click', '.mainGridComponent .pagerItem', function(){
        if($(this).hasClass('active')){
            return false;
        }
        let page = $(this).attr('data-page');
        let parent = $(this).parents('.mainGridComponent')[0];
        let className = $(parent).attr('data-class');
        let url = $(parent).attr('data-url');
        let data = {};
        data['params'] = $(parent).attr('data-params');
        data['page'] = page;
        Ajax.ajax(
            url + '/ajax.php',
            'POST',
            data,
            function(html) {
                    $('.ajaxBlock' + className).html(html);
                    console.log(html);
                }
        );

    });
});