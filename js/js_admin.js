/* =====================================================================================
*
*  Delete button
*
*/

function deleteButtonQuote(num) {
	jQuery("#wait"+num).show();
	//Supprime la ligne
	var arguments = {
		action: 'delete_link', 
		idLink : num
	} 
	//POST the data and delete the line
	
	jQuery.fn.fadeThenSlideToggle = function(speed, easing, callback) {
	  	if (this.is(":hidden")) {
			return this.slideDown(speed, easing).fadeTo(speed, 1, easing, callback);
	  	} else {
			return this.fadeTo(speed, 0, easing).slideUp(speed, easing, callback);
	  	}
	};
	
	jQuery.post(ajaxurl, arguments, function(response) {
		jQuery("#wait"+num).fadeOut();
		//jQuery("#quote"+num).html(response);
		jQuery("#ligne"+num).fadeThenSlideToggle();
	});    
	
	return false ; 
}

/* =====================================================================================
*
*  Affiche le formulaire de modification de la citation
*
*/

function modifyButtonQuote(num) {					
	var response = "<textarea name='newquotes"+num+"' id='newquotes"+num+"' rows='4' cols='55'>"+jQuery("#quote"+num).html()+"</textarea><br/>" ; 
	response += "<input type='submit' name='' id='valid"+num+"' class='button-primary validButton' value='Update' onclick='validButtonQuote("+num+");' />" ; 
	response += "<input type='submit' name='' id='cancel"+num+"' class='button cancelButton' value='Cancel' onclick='cancelButtonQuote("+num+");' />" ; 
	jQuery("#quote"+num).html(response);
	jQuery("#button"+num).hide();
	
}

/* =====================================================================================
*
*  Cancel du formulaire
*
*/

function cancelButtonQuote (num) {
	jQuery("#wait"+num).show();
	var arguments = {
		action: 'cancelQuotes', 
		id : num
	} 
	//POST the data and append the results to the results div
	jQuery.post(ajaxurl, arguments, function(response) {
		jQuery("#wait"+num).fadeOut();
		jQuery("#quote"+num).html(response);
		jQuery("#button"+num).show();
	}); 
	
}

/* =====================================================================================
*
*  Valid du formulaire
*
*/

function validButtonQuote (num) {
	jQuery("#wait"+num).show();
	var arguments = {
		action: 'validQuotes', 
		id : num,
		quote : document.getElementById("newquotes"+num).value
	} 
	
	//POST the data and append the results to the results div
	jQuery.post(ajaxurl, arguments, function(response) {
		jQuery("#wait"+num).fadeOut();
		jQuery("#quote"+num).html(response);
		jQuery("#button"+num).show();
	});    
}