
//**********************************************************************************************************************
//
function printDocuments(url)
{
 var request = new Ajax.Request(
        url,
        {
            method: 'GET'
        }
    );
}

//**********************************************************************************************************************
//
function displaySerialCount(id)
{
	var serialText = document.getElementById('serials_' + id).value;
	var spanCount = document.getElementById('serial_nb_' + id);
	
	var t_serials = serialText.split("\n");
	var nb = 0;
	var i;
	for(i=0;i<t_serials.length;i++)
	{
		if (t_serials[i] != '')
			nb++;
	}
	
	spanCount.innerHTML = nb + ' serials';
}

//**********************************************************************************************************************
//
function performAction(action)
{
	var t = action.split(";");
	var type = t[0];
	var url = t[1];
	
	switch(type)
	{
		case 'download':
			document.location.href = url;
			break;
		case 'ajax':
			 var request = new Ajax.Request(
			        url,
			        {
			            method: 'GET'
			        }
			    );
			break;
		case 'redirect':
			document.location.href = url;
			break;
	}
	
	document.getElementById('actions_list').selectedIndex = 0;
}

//*****************************************************************************************************************************************
//call an url using ajax
function ajaxCall(url)
{
	 var request = new Ajax.Request(
	        url,
	        {
	            method: 'GET',
	            onSuccess: function onSuccess(transport)
		        			{
		        				elementValues = eval('(' + transport.responseText + ')');
		        				//alert(elementValues['message']);
		        			},
				onFailure: function onFailure(transport)
		        			{
								alert('error');
		        			},

	        }
	    );	
}

//*****************************************************************************************************************************************
//commit button
function commit(saveData, createShipmentInvoices, printDocuments, downloadDocuments, printShippingLabel, selectNexOrder)
{
	//define setttings
	if (createShipmentInvoices)
		document.getElementById('create').value = 1;		
	
	if (printDocuments)
		document.getElementById('print_documents').value = 1;		
	
	if (printShippingLabel)
		document.getElementById('print_shipping_label').value = 1;		

	//call main method using ajax
    var request = new Ajax.Request(
        saveDataUrl,
        {
            method: 'post',
            onSuccess: function onSuccess(transport)
		        			{
		        				elementValues = eval('(' + transport.responseText + ')');
		        				if (elementValues['error'] == true)
		        				{
		        					alert(elementValues['message']);
		        				}
		        				else
		        				{
								    //download documents (invoice & shipment)
									if (downloadDocuments)
										document.location.href = downloadDocumentUrl;
									
									//select next order
									if (selectNexOrder)
										document.location.href = nextOrderUrl;
									else
										document.location.href = refreshUrl;
										
		        				}
		        			},
			onFailure: function onFailure(transport)
		        			{
								alert('error');
		        			},
            parameters: Form.serialize(document.getElementById('form_onepage_preparation'))
        }
    );

}