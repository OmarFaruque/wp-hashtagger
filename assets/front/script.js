jQuery(document).ready(function($){
    //setTimeout(loadajax,10000);
    /*
    * Registration Form Submission 
    */
    $(document.body).on('submit', 'form.r_form, form#r_form', function(event){
        $tag = $(this).find('input[name="r_tag"]').val();
        $tag = $tag.split('#').join('');
        $user_id = $(this).find('input[name="r_user_id"]').val();
        $this = $(this);
        $localAray = [];
        
        event.preventDefault();
         $.ajax({
            type:'POST', 
            //dataType: "json",
            url: hastagger_ajax.ajaxurl,
            data:
            {
                'action'    : 'register_tags',
                'tag'       : $tag
            },success:function(data){
                console.log($.trim(data));
                if($.trim(data) == 'success'){
                    if($this.hasClass('r_form')){
                        $this.text('#'+$tag+' Registation successfully Complete.');
                        $localrgTags = localStorage.getItem("rg_tags");
                        $localAray.push($localrgTags);
                        $localAray.push($tag);
                        localStorage.setItem('rg_tags', $localAray);

                    }else{
                        $liHTML = '<li><span class="itemN">'+$tag+'</span><span data-item="'+$tag+'" class="delete"><i class="fa fa-times-circle" aria-hidden="true"></i></span></li>';
                        $('.rg_list ul').append($liHTML);
                        $this.find('input[name="r_tag"]').val('');
                    }
                }
            }
        });
    });
	

    //Delete Registration Items 
    $(document.body).on('click', '.rg_list ul li span.delete', function(){
        $itemN = $(this).data('item');
        $email = $('form#r_form input[name="r_email"]').val();
        $li    = $(this).closest('li');
        $localL = localStorage.getItem("rg_tags");
        $lArray = $localL.split(',');
        $.ajax({
            type:'POST', 
            //dataType: "json",
            url: hastagger_ajax.ajaxurl,
            data:
            {
                'action'    : 'hastag_delete',
                'tag'       : $itemN,
                'email'     : $email
            },success:function(data){
                //console.log($.trim(data));
                if($.trim(data) == 'success'){
                    $li.remove();
                    $index = $lArray.indexOf($itemN);
                    $lArray.splice($index, 1);
                    localStorage.setItem('rg_tags', $lArray);
                     $localLa = localStorage.getItem("rg_tags");
                    //console.log($localLa);
                }
            }
        });
    });



//localStorage.removeItem("rg_tags");

// Tooltip 
           if(current_user_id && active_email){ 
           $localL = localStorage.getItem("rg_tags");
           $lArray = ($localL)?$localL.split(','):[];
           $lArray = $lArray.filter(function(v){return v!==''});
          console.log($lArray);
           $('a.hastag_c_1').each(function() { 
            $thisData = $(this).data('name');
            if($lArray.indexOf($thisData) > -1){
                $text = 'You have already registered.';
            }else{
                $text= '<form method="post" action="" class="r_form '+$(this).data('name')+'">'
                  +'<input type="hidden" name="r_tag" value="'+$(this).data('name')+'"/>'
                  +'<button class="pull-right btn btn-primary btn-hashtag">Hit Me!</button>'
                  +'</form>';
            }
                    $(this).qtip({ // Grab some elements to apply the tooltip to
                    content: {
                        text: $text, 
                        title: {
                        text: 'Active Email notification',
                        button: true
                        }
                    },
                     position: {
                        corner: {
                            tooltip: 'bottomLeft', // Use the corner...
                            target: 'topRight' // ...and opposite corner
                        }
                    },
                    show: {
                        solo: true,
                        when: false, // Don't specify a show event
                        ready: true // Show the tooltip when ready
                    },
                    hide: {when: {event:'mouseout unfocus'}, fixed: true, delay: 500}, // Don't specify a hide event
                    });
                    }); //end qtip
       }








}); // End Document Ready


function findAndReplace(searchText, replacement, searchNode) {
    if (!searchText || typeof replacement === 'undefined') {
        // Throw error here if you want...
        return;
    }
    var regex = typeof searchText === 'string' ?
                new RegExp(searchText, 'g') : searchText,
        childNodes = (searchNode || document.body).childNodes,
        cnLength = childNodes.length,
        excludes = 'html,head,style,title,link,meta,script,object,iframe';
    while (cnLength--) {
        var currentNode = childNodes[cnLength];
        if (currentNode.nodeType === 1 &&
            (excludes + ',').indexOf(currentNode.nodeName.toLowerCase() + ',') === -1) {
            arguments.callee(searchText, replacement, currentNode);
        }
        if (currentNode.nodeType !== 3 || !regex.test(currentNode.data) ) {
            continue;
        }
        var parent = currentNode.parentNode,
            frag = (function(){
                var html = currentNode.data.replace(regex, replacement),
                    wrap = document.createElement('div'),
                    frag = document.createDocumentFragment();
                wrap.innerHTML = html;
                while (wrap.firstChild) {
                    frag.appendChild(wrap.firstChild);
                }
                return frag;
            })();
        parent.insertBefore(frag, currentNode);
        parent.removeChild(currentNode);
    }
}


function loadajax(){
$localSmail = localStorage.getItem("hash_email");
    $localSname = localStorage.getItem("hash_name");
    $fromLmail = ($localSmail !== null)?$localSmail:'';
    $fromLname = ($localSname !== null)?$localSname:''
    var bodyText = $('body').text();
    var tagslistarr = bodyText.replace( /\n/g, " " ).split( " " );
    tagslistarr = tagslistarr.map(function (el) {
        return el.trim();
    });
    var arr=[];

    $.each(tagslistarr,function(i,val){
        if(tagslistarr[i].indexOf('#') == 0){
          arr.push(tagslistarr[i]);

          var splitTag = tagslistarr[i].split('#').join('');

          $.ajax({
            type:'POST', 
            dataType: "json",
            url: hastagger_ajax.ajaxurl,
            data:
            {
                'action'    : 'check_tags',
                'tag'       : splitTag,
                'l_mail'    : $fromLmail
            },success:function(data){
                console.log(data);    
                if(data){
                
                if(data.symbol != null){$dispTag = tagslistarr[i]}else{$dispTag = splitTag;}
                $output = '<a class="'+data.css_class+' mkLsJs '+data.name+'" href="'+base_url+'/tag/'+splitTag+'">'+$dispTag+'('+data.post_count+')</a>';
                findAndReplace(tagslistarr[i], $output);
                $text = '';
                if(data.al_reg && data.al_reg == 0)
                {
                $text +='<form method="post" action="" class="r_form '+data.name+'">'
                        +'<input type="hidden" name="r_tag" value="'+data.name+'"/>'
                        +'<div class="form-group"><input value="'+$fromLname+'" name="r_name" placeholder="Name" class="form-control" type="text"/></div>'
                        +'<div class="form-group"><input value="'+$fromLmail+'" type="email" name="r_email" placeholder="E-mail" class="form-control"/></div>'
                        +'<button class="pull-right btn btn-primary btn-hashtag">Hit Me!</button>'
                        +'</form>';
                }
                else
                {
                    $text +='You have already registered.';
                }
                if(data.tooltip){
                    $('.mkLsJs.'+data.name).qtip({ // Grab some elements to apply the tooltip to
                    content: {
                        text: $text, 
                        title: {
                        text: 'Active Email notification',
                        button: true
                        }
                    },
                     position: {
                        corner: {
                            tooltip: 'bottomLeft', // Use the corner...
                            target: 'topRight' // ...and opposite corner
                        }
                    },
                    show: {
                        solo: true,
                        when: false, // Don't specify a show event
                        ready: true // Show the tooltip when ready
                    },
                    hide: {when: {event:'mouseout unfocus'}, fixed: true, delay: 500}, // Don't specify a hide event
                    }); //end qtip
                }//Check if tooltip action from admin
            } //if data back
            }
          });
            
          
        }
    }); //each tagslistarr
}





