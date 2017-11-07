jQuery(document).ready(function($) {
	
	'use strict';

	new Clipboard('#e-goi_shortcode');

	var session_form = $('#session_form');

	// Async fetch
	url = window.location.href;

	var page = url.split('?page=');

	if(typeof page[1] != 'undefined'){
		if(page[1].substr(0, 14) == 'egoi-4-wp-form'){

			var data_lists = {
		        action: 'egoi_get_lists'
		    };
		    var select_lists_frm = $('#e-goi-list-frm');
		    var select_lists_bar = $('#e-goi-list-bar');
		    var current_lists = [];

		    $(".loading_lists").addClass('spin').show();
		    var lists_count_frm = $('#e-goi-lists_ct_forms');
		    var lists_count_bar = $('#e-goi-lists_ct_bar');
		    
		    $.post(url_egoi_script.ajaxurl, data_lists, function(response) {
			    
			    $(".loading_lists").removeClass('spin').hide();
			    current_lists = JSON.parse(response);
				
				if(current_lists.ERROR){
					$('.e-goi-lists_not_found').show();

					select_lists_frm.hide();
					select_lists_bar.hide();
				}else{
					select_lists_frm.show();
					select_lists_bar.show();
					$('.e-goi-lists_not_found').hide();

					$.each(current_lists, function(key, val) {
			        	
			        	if(typeof val.listnum != 'undefined') {
				        	select_lists_frm.append($('<option />').val(val.listnum).text(val.title));
				        	select_lists_bar.append($('<option />').val(val.listnum).text(val.title));
				        	
				        	if(lists_count_frm.text() === val.listnum){
				        		select_lists_frm.val(val.listnum);
				        	}

				        	if(lists_count_bar.text() === val.listnum){
				        		select_lists_bar.val(val.listnum);
				        	}
			            }
			        });	
				    	
				}
			});
		}
	}
    // End of Async fetch

	$('#rcv_e-goi_forms').text($('#ct_e-goi_forms').text());

	$('#egoi4wp-form-hide').hide();
	$('#wp-form_content-editor-tools').append('<b>Editor</b>');

	var $context = $(document.getElementsByClassName('wrap'));
	$context.find('.color').wpColorPicker();

	var $content = $(document.getElementById('tab-content'));
	var $appearance = $(document.getElementById('tab-appearance'));

	$appearance.hide();

	$('#nav-tab-content').click(function() {

		$content.show();
		$appearance.hide();

		$('#nav-tab-content').addClass('nav-tab-active');
		$('#nav-tab-appearance').removeClass('nav-tab-active');
	});

	$('#nav-tab-appearance').click(function() {

		$appearance.show();
		$content.hide();

		$('#nav-tab-appearance').addClass('nav-tab-active');
		$('#nav-tab-content').removeClass('nav-tab-active');
	});

	$('#close_egoi').click(function() {
		$('#TB_closeWindowButton').trigger("click");
	});


	// alert change 
	$('#form_choice').on('change', function() {
		
		if(session_form.length){
			$('.cd-popup-trigger-change').trigger('click');

		}else{
			$("#load_frm_change").addClass('spin').show();
			document.getElementById("e-goi-form-options").submit();
		}

	});

	// POPUP 
	//open popup
	$('.cd-popup-trigger-del').on('click', function(event){
		event.preventDefault();
		$('.cd-popup-del-form').addClass('is-visible');
	});

	$('.cd-popup-trigger-change').on('click', function(event){
		event.preventDefault();
		$('.cd-popup-change-form').addClass('is-visible');
	});
	
	//close popup
	$('.cd-popup').on('click', function(event){
		if( $(event.target).is('.cd-popup-close-btn') || $(event.target).is('.cd-popup') ) {
			event.preventDefault();
			$(this).removeClass('is-visible');
		}
	});

	// cancel btn on change form
	$('#close_frm_change').on('click', function(){
		$('#form_choice').val($('#type_frm_saved').val());
	});

	// alert popup on change form type
	$('#change_form_req').click(function(){
		document.getElementById("e-goi-form-options").submit();
	});


	// OTHER THINGS
	$('#get_type_form').click(function() {
		$('#form_type').trigger("click");
	});

	// on change list
    $('#e-goi-list-frm').on('change', function() {
       
        var listID = $(this).val();
        var block = $('#formid_egoi');
        var data = {
            action: 'get_form_from_list',
            listID: listID
        };

        $("#load_forms").addClass('spin').show();

        var content = '';
        
        $.post(url_egoi_script.ajaxurl, data, function(response) {
            
            $("#load_forms").removeClass('spin').hide();

			content = JSON.parse(response);
			if(content.ERROR){
				$('#egoi_form_wp').hide();
				$('#empty_forms').show();
				return false;

			}else{

				$('#egoi_form_wp').show();
				$('#empty_forms').hide();

	            block.show();
	            $.each(content, function(key, val) {
	            	if(typeof val.id != 'undefined'){
	                	block.append('<option value="' + val.id + ' - ' + val.url + '">' + val.title + '</option>');
	          		}
	          	});
	          	$('#formid_egoi').trigger('change');
	        }
        });

    });


	// ---------FORM E-GOI ---
	$('#formid_egoi').on('change', function() {
		var e = document.getElementById('formid_egoi');
		var strUser = e.options[e.selectedIndex].value;
		var res = strUser.split(" - ");

		if(strUser != ''){
			$.ajax({
			    url: '//'+window.location.host+'/wp-content/plugins/smart-marketing-for-wp/admin/partials/custom/egoi-for-wp-form_egoi.php',
			    type: 'POST',
			    data:({
			        id: res[0],
			        url: res[1]
			    }),
			    success:function(data, status) {

			    	if(data){
				        $('#egoi_form_inter').html(data);
				        $('#form_egoint').trigger('click');

				        var TBwindow = $('#TB_window');
						$('#TB_ajaxContent').css('width', '700px');
				  		TBwindow.css('width', '730px');
				  	}
			    },
			    error:function(status){
				    $("#valid").hide();
				    $("#error").show();
			    }
			});
		}
	});


    var url_string = window.location.href;
    var url = new URL(url_string);
    var c = url.searchParams.get("type");
    if (c != 'form') {
        $('#egoi4wp-form-hide').show();
    }else{
        $('#egoi4wp-form-hide').hide();
    }

});
