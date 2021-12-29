/**
* NOTICE OF LICENSE
*
* This file is licenced under the Software License Agreement.
* With the purchase or the installation of the software in your application
* you accept the licence agreement.
*
* You must not modify, adapt or create derivative works of this source code
*
*  @author    Tendencys Innovations
*  @copyright 2019-2020 Tendencys
*  @license   LICENSE.txt
*/

var enviaHostname = "";
var shop = "";
var token = "";
var shopStatus = "";
var errorCode = "";
var errorMsg = "";

jQuery(document).on('click', '.enviaBtn', function(){
	var shop = jQuery(this).data('shop'),
		id = jQuery(this).data('id'),
		hash = jQuery(this).data('info'),
		token = jQuery(this).data('token'),
		error = null,
		type = jQuery(this).attr('data-type'),
		url = enviaHostname+'/integrate/createlabel?shop='+shop+'&woocommerce=true&id='+id+'&mode='+type;

	if(jQuery(this).data('error-code') != undefined)
		error = jQuery(this).data('error-code');

	if(error != null){
		url = enviaHostname+'/integrate/install?shop='+shop+'&woocommerce=true';
	}
	jQuery(document).find('#iframe').prop('src', function ( i, val ) { return url; });
	jQuery(document).find('#enviaPrestashopModal').modal('show');
	jQuery("#collapse-button").trigger("click");

	document.getElementById('iframe').onload = function(){
		if(jQuery('#enviaPrestashopModal #preloader').is(':visible')) jQuery('#enviaPrestashopModal #preloader').fadeOut('slow');
	}
});

jQuery(document).on('hidden.bs.modal', '#enviaPrestashopModal', function (e) {
	
	if(jQuery('#enviaPrestashopModal #preloader').is(':hidden')) jQuery('#enviaPrestashopModal #preloader').show();
	var count = jQuery(document).find('#iframe').contents().find('#alert_paqueteria .alert-success').length,
		iframe = jQuery(document).find("#enviaPrestashopModal #iframe"),
		element = jQuery("#alert_paqueteria .alert-success", iframe.contents());

	if(count > 0){
		location.reload();
	}
	jQuery(document).find('#iframe').prop('src', function ( i, val ) { return ''; });
});

jQuery(document).ready(function(){
	var jquery = document.querySelector("input[data-name=enviaJQuery]");
	if(jquery !== null){
		enviaHostname = jquery.getAttribute("data-hostname");
		shop = jquery.getAttribute("data-shop");
		token = jquery.getAttribute("data-token");
		shopStatus = jquery.getAttribute("data-shop-status");
		errorCode = jquery.getAttribute("data-shop-error-code");
		errorMsg = jquery.getAttribute("data-shop-error-msg");
		var ajax_confirmation = jQuery(document).find("#ajax_confirmation");
	}

	if(shopStatus == "FALSE"){
		ajax_confirmation.html("Tu m&oacute;dulo de <strong>Env&iacute;a.com</strong> no se encuentra correctamente sincronizado o tus Claves de Acceso se encuentran desactualizadas. <a href=\""+enviaHostname+'/integrate/install?shop='+shop+'&woocommerce=true&token='+token+"&continue=true\" target=\"_blank\">Sincronizar M&oacute;dulo Ahora.</a>");
		if(ajax_confirmation.hasClass("hide")){
			ajax_confirmation.removeClass("hide");
		}

		if(ajax_confirmation.hasClass("alert-success")) ajax_confirmation.removeClass("alert-success");
		if(ajax_confirmation.hasClass("alert-danger")) ajax_confirmation.removeClass("alert-danger");
		if(!ajax_confirmation.hasClass("alert-warning")) ajax_confirmation.addClass("alert-warning");
	}
});

jQuery(document).on("change", "[name='orderBox[]']", function(){
	var ids = [],
		url = enviaHostname+"/integrate/createmultilabel?shop="+shop+"&woocommerce=true&";
	jQuery("[name='orderBox[]']:checked").each(function(i, e){
		ids.push("ids[]="+jQuery(e).val());
	});

	url += ids.join("&");

	if(jQuery(".envia-multilabels").length == 0){
		btn = "<div class=\"col-lg-6 text-right\"><a class=\"btn btn-default bulk-actions envia-multilabels enviaBtn\" target=\"_blank\" style=\"margin-left:10px;padding:0;\" href=\""+url+"\"><span class=\"img\"><img src=\"https://s3.us-east-2.amazonaws.com/enviapaqueteria/uploads/images/logo-enviapaqueteria.png\" alt=\"\"></span><span>Generar Multiguías</span></a></div>";
		jQuery(".bulk-actions").parents(".col-lg-6").after(btn);
		jQuery("#table-order").parents(".table-responsive-row").prepend("<div class=\"row\" style=\"margin-bottom: 17px;\"><div class=\"col-lg-6 text-right\">&nbsp;</div>"+btn+"</div>");
	}else{
		if(ids.length > 0){
			jQuery(".envia-multilabels").attr("href", url);
		}else{
			jQuery(".envia-multilabels").remove();
		}
	}
});

jQuery(document).on("click", ".shipping-rates", function(e){
	e.preventDefault();
	const url = jQuery(this).attr("data-url");
	window.open(jQuery("#ENVIA_APP_HOSTNAME").attr("data-env")+"/configuraciones/integraciones/ecommerce-rates?shop="+url);
});

jQuery(document).on("click", ".shipping-config", function(e){
	e.preventDefault();
	const url = jQuery(this).attr("data-url");
	window.open(jQuery("#ENVIA_APP_HOSTNAME").attr("data-env")+"/integrate/config?url="+url);
});
