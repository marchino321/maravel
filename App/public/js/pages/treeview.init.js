$(document).ready(function () {
    
    $("#TreeStrumenti").jstree({ 
        core: { 
            themes: { 
                responsive: !1 
            } 
        }, 
        types: { 
            default: { 
                icon: "mdi mdi-folder-star" 
            }, 
            file: { 
                icon: "mdi mdi-file" 
            } 
        }, 
        plugins: ["types", "state"] 
    });

    $('#TreeStrumenti').on("select_node.jstree.jstree", function (e, data) {
        let dati = data.selected[0];
        
        const replacedString = dati.replace("IdCategoria_", "");
        $('#categoria_id').val(replacedString);
   
        
        
    });

    
    
});
