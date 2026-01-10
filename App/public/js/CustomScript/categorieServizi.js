
function AggiungiCategoriaServizi(parentId, nomeCategoria) {

    // Logica per aggiungere una categoria
    let Titolo = "Aggiungi una ";
    if (parentId === 0) {
        // Logica per aggiungere una categoria padre
        Titolo += "Categoria Padre";
    } else {
        // Logica per aggiungere una categoria figlia
        Titolo += "sotto categoria a: " + nomeCategoria;
    }
    let body = `
    <input type="hidden" name="id_parent" id="id_parent" class="form-control" value="${parentId}">
        <div class="col-md-12 mt-3 mb-3">
            <label for="" class="form-label">Nome Categoria <span class="text-danger">*</span></label>
            <input type="text" name="nuova_categoria" id="nuova_categoria" class="form-control" >
        </div>
        <div class="col-md-12 mt-3 mb-3" id="messaggio">
            
        </div>
    `;
    let bottoni = `
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Chiudi</button>
        <button type="button" class="btn btn-success" onclick="SalvaCategoriaServizi()">Salva</button>
    `;
    MostraModal(Titolo, body, bottoni);

}
function ModificaCategoriaServizi(idCategoria, nomeCategoria) { 


    let Titolo = "Modifica la categoria: " + nomeCategoria;
    let body = `
    <input type="hidden" name="id_categoria" id="id_categoria" class="form-control" value="${idCategoria}">
        <div class="col-md-12 mt-3 mb-3">
            <label for="nuova_categoria" class="form-label">Nome Categoria <span class="text-danger">*</span></label>
            <input type="text" name="nuova_categoria" id="nuova_categoria" class="form-control" value="${nomeCategoria}" >
        </div>
        <div class="col-md-12 mt-3 mb-3" id="messaggio">
            
        </div>
    `;
    let bottoni = `
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Chiudi</button>
        <button type="button" class="btn btn-success" onclick="EditCategoriaServizi()">Salva</button>
    `;
    MostraModal(Titolo, body, bottoni);
}
function DistruggiTreeServizi() {
    $('#TreeServizi').jstree("destroy").empty();
}

function RicostruisciTreeServizi() {
    $("#TreeServizi").jstree({ 
        core: { 
            themes: 
            { 
                responsive: !1 
            } }, 
            types: { 
                default: { 
                    icon: "mdi mdi-server" 
                }, 
                file: { 
                    icon: "mdi mdi-server-plus" 
                } }, 
                plugins: ["types", "state"] 
            });
}
function SalvaCategoriaServizi()
{
    let nuovaCategoria = document.getElementById("nuova_categoria").value;
    let id_parent = document.getElementById("id_parent").value;
    if (nuovaCategoria != "") {
        DistruggiTreeServizi();
        // Logica per salvare la categoria
        $('#messaggio').html("");


        AjaxHelper.post("/private/ServiziAjax/AddCategoriaServizi", {
            id_parent: id_parent, 
            nuova_categoria: nuovaCategoria
        })
            .then(risp => {
                //console.log("✅ Risposta server:", risp);

                if (!risp.success) {
                    console.error("❌ Errore dal server:", risp.error, risp.trace);
                    return;
                }

                $('#categorie_servizi').html(risp.data);
                RicostruisciTreeServizi();
                ChiudiModal();
            })
            .catch(err => {
                console.error("❌ Errore:", err);
            });


    } else {
        $('#messaggio').html('<p class="text-danger">Il nome della categoria non può essere vuoto!</p>');
    }
}

function EditCategoriaServizi(){
    let nuovaCategoria = document.getElementById("nuova_categoria").value;
    let id_categoria = document.getElementById("id_categoria").value;
    if (nuovaCategoria != "") {
        DistruggiTreeServizi();


        AjaxHelper.post("/private/ServiziAjax/EditCategoria", {
            nuovaCategoria: nuovaCategoria, 
            id_categoria: id_categoria
        })
            .then(risp => {
                //console.log("✅ Risposta server:", risp);

                if (!risp.success) {
                    //console.error("❌ Errore dal server:", risp.error, risp.trace);
                    return;
                }

                $('#categorie_servizi').html(risp.data);
                RicostruisciTreeServizi();
                ChiudiModal();
            })
            .catch(err => {
                //console.error("❌ Errore:", err);
            });

        $('#messaggio').html('');
    } else {
        $('#messaggio').html('<p class="text-danger">Il nome della categoria non può essere vuoto!</p>');
    }
}

RicostruisciTreeServizi();


$('#TreeServizi').on("select_node.jstree.jstree", function (e, data) {
    let dati = data.selected[0];

    const replacedString = dati.replace("IdCategoria_", "");
    $('#category_id_servizio').val(replacedString);



});

