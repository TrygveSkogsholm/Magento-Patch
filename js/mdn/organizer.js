
//****************************************************************************************************************
//retourne l'url de l'admin
function getAdminUrl()
{
	return OrganizerBaseUrl;
}

function addKeyParam()
{
	return '/key/' + FORM_KEY;
}

//****************************************************************************************************************
//Soumet le formulaire de création de tache
function SubmitForm(taskId, gridGuid)
{
	//Soumet la requete en ajax
	var url;
	url = getAdminUrl() + 'Organizer/Task/Save';
	
	//Appel l'url en ajax
	var request = new Ajax.Request(
		url,
	    {
	        method:'POST',
	        onSuccess: function onSuccess(transport)
	        			{
	        				elementValues = eval('(' + transport.responseText + ')');
	        				alert(elementValues['message']);

	        				//rafraichit la liste si c une création & vide le form
	        				if (taskId == '')
	        				{
	        					var entityType = document.getElementById('ot_entity_type').value;
	        					var entityId = document.getElementById('ot_entity_id').value
		        				RefreshTaskList(gridGuid, entityType, entityId);
	        					document.getElementById('div_edit_task_' + taskId).style.display = 'none';
	        					document.getElementById('edit_form_task_' + taskId).reset();
	        				}
	        			},
	        onFailure: function onFailure() 
	        			{
	        				alert('error');
	        			},
            parameters: Form.serialize(document.getElementById('edit_form_task_' + taskId))
	    }
    );
}

//****************************************************************************************************************
//rafraichit la liste des taches
function RefreshTaskList(gridGuid, entityType, entityId)
{
	var div;
	div = document.getElementById('OrganizerGrid' + gridGuid);
	if (div)
	{
		//definit l'url
		var url = getAdminUrl() + 'Organizer/Task/EntityList'

		new Ajax.Updater('OrganizerGrid' + gridGuid, url, {
            method: 'post',
            parameters : {'form_key': FORM_KEY, 'entity_type': entityType, 'entity_id': entityId},
            evalScripts : true
        });
		
	}
}

//****************************************************************************************************************
//Supprime une tache
function Delete(taskId, gridGuid)
{
	if (window.confirm('Are you sure ?'))
	{
		var url = getAdminUrl() + 'Organizer/Task/Delete/';
		var request = new Ajax.Request(
				url,
			    {
			        method:'POST',
			        onSuccess: function onSuccess(transport)
			        			{
			        				elementValues = eval('(' + transport.responseText + ')');
			        				alert(elementValues['message']);
		
			        				//Supprime les lignes du tableau
									var position = findRowPosition(taskId, gridGuid);
									document.getElementById('OrganizerGrid' + gridGuid + '_table').deleteRow(position + 1);
									document.getElementById('OrganizerGrid' + gridGuid + '_table').deleteRow(position);
			        			},
			        onFailure: function onFailure() 
			        			{
			        				alert('error');
			        			},
			        parameters : {'form_key': FORM_KEY, 'ot_id': taskId}
			    }
		    );
	}
}

//****************************************************************************************************************
//Notifie la cible
function Notify(taskId)
{
		var url = getAdminUrl() + 'Organizer/Task/Notify/';
		
		var request = new Ajax.Request(
				url,
			    {
			        method:'POST',
			        onSuccess: function onSuccess(transport)
			        			{
			        				alert('Target Notified');
			        			},
			        onFailure: function onFailure() 
			        			{
			        				alert('error');
			        			},
			        parameters : {'form_key': FORM_KEY, 'ot_id': taskId}
			    }
		    );
}

//****************************************************************************************************************
//Affiche la tache pour l'éditer (ou juste la consulter)
function editTask(taskId, gridGuid)
{
	//Défini la position de la ligne dans le table
	var position = findRowPosition(taskId, gridGuid);
	
	//Verifie si on doit afficher ou masquer
	var rowExist = false;
	var rowName = 'row_edit' + gridGuid + '_task_' + taskId;
	var existingRow = document.getElementById(rowName);
	if (existingRow != null)
	{
		if (existingRow.style.display == '')
			existingRow.style.display = 'none';
		else	
			existingRow.style.display = '';
	}
	else
	{
		//rajoute la ligne au tableau
		var tableID = 'OrganizerGrid' + gridGuid + '_table';
		var table = document.getElementById(tableID);
		var row = table.insertRow(position + 1);
		row.id = rowName;
		var cell1 = row.insertCell(0);   
		cell1.colSpan = 10;
	    var editDiv = document.createElement("div");   
		editDiv.id = "div_edit" + gridGuid + "_task_" + taskId;   
	    cell1.appendChild(editDiv);   		

	    //Charge le formulaire pour l'édition de la tache
	    var url = getAdminUrl() + 'Organizer/Task/Edit/ot_id/' + taskId + '/guid/' + gridGuid;
		new Ajax.Updater(editDiv.id,
						 url,
						 {evalScripts:true}
						 );
	    
	}
}

//****************************************************************************************************************
//trouve la position de la ligne pour un taskid
function findRowPosition(taskId, gridGuid)
{
	var tableID = 'OrganizerGrid' + gridGuid + '_table';
	var table = document.getElementById(tableID);
	var count = table.rows.length;
	var position = 0;
	var i;
	for (i=0;i<count;i++)
	{
		var colCount = table.rows[i].cells.length;
		if (colCount > 2)
		{
			if (table.rows[i].cells[0].innerHTML == taskId)
				return i;
		}
	}
	
	alert('pas trouvé');
	return position;
}

//****************************************************************************************************************
//trouve la position de la ligne pour un taskid
function toggleNewTask(gridGuid)
{
	if (document.getElementById('div_edit_task_').style.display == '')
		document.getElementById('div_edit_task_').style.display = 'none';
	else
		document.getElementById('div_edit_task_').style.display = '';
}

//****************************************************************************************************************
//
function toggleDiv(divId)
{
	if (document.getElementById(divId))
	{
		if (document.getElementById(divId).style.display == '')
			document.getElementById(divId).style.display = 'none';
		else
			document.getElementById(divId).style.display = '';
	}
}