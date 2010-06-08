
//Function to update availability for configurable product
function updateAvailability(spConfig)
{

	//retrieve product id
	var productId;
	productId = null;
    for(var i=spConfig.settings.length-1;i>=0;i--){
        var selected = spConfig.settings[i].options[spConfig.settings[i].selectedIndex];
        if(selected.config){
        	if (productId == null)
	            productId = selected.config.products;
        }
    }
    
    //get product information
    var information;
    information = null;
    for (var i=0;i<spConfig.config.subProductsAvailability.length;i++)
    {
    	if (spConfig.config.subProductsAvailability[i].id == productId)
    		information = spConfig.config.subProductsAvailability[i];
    }
    
    //display information
    var mainDiv;
    mainDiv = document.getElementById('div_availability');
	if (information == null)
	{
		//if no information found, hide availability block
		mainDiv.style.display = 'none';
	}
	else
	{
		//show availability block
		mainDiv.style.display = '';		
		
		//display informations
		document.getElementById('span_product_availability_additional_information').innerHTML = information.description;
		document.getElementById('span_product_availability').innerHTML = information.availability;
	}
}

