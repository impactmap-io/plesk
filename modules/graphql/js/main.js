function save_graphql() {
	"use strict"; 
    $.post(admin_url + 'graphql/save', {
        admin_area: $('#graphql_admin_area').val(),
        clients_area: $('#graphql_clients_area').val(),
        clients_and_admin: $('#graphql_clients_and_admin_area').val(),
    }).done(function(response) {
        window.location = admin_url + 'graphql';
    });
}

function enable_graphql() {
	"use strict"; 
    $.post(admin_url + 'graphql/enable', {}).done(function() {
        window.location = admin_url + 'graphql';
    });
}

function disable_graphql() {
	"use strict"; 
    $.post(admin_url + 'graphql/disable', {}).done(function() {
        window.location = admin_url + 'graphql';
    });
}