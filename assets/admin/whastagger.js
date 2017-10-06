jQuery(document).ready(function($){
	$(document.body).on('submit', 'form.sabai-form', function(e){
		$url = document.URL;
		var url = new URL($url);
		var c = url.searchParams.get("q");
		var r = c.split('/');
		var id = r[2];
		$content = $('textarea[name="field_contexte[0]"]').val();
		   $.ajax({
            type:'POST', 
            //dataType: "json",
            url: admin_hastagger_ajax.admin_ajaxurl,
            data:
            {
                'action'    : 'sabai_hashtag_process',
                'content'   : $content,
                'id' 		: id
            },success:function(data){
                console.log($.trim(data));
            }
        });
		
		//e.preventDefault();
	});
});