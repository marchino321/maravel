$(document).ready(function () {

    RicostruisciTreeListini();

    $('#TreeListini').on("select_node.jstree.jstree", function (e, data) {
        let dati = data.selected[0];

        const replacedString = dati.replace("IdCategoria_", "");
        $('#category_id').val(replacedString);



    });



});
function DistruggiTreeListini() {
    $('#TreeListini').jstree("destroy").empty();
}

function RicostruisciTreeListini() {
    $("#TreeListini").jstree({
        core: {
            themes: {
                responsive: !1
            }
        },
        types: {
            default: {
                icon: "mdi mdi-basket-fill"
            },
            file: {
                icon: "mdi mdi-basket-plus"
            }
        },
        plugins: ["types", "state"]
    });
}
//"checkbox"();

function AggiungiCategoria(parentId, nomeCategoria) {
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
        <button type="button" class="btn btn-success" onclick="SalvaCategoriaListini()">Salva</button>
    `;
    MostraModal(Titolo, body, bottoni);
}
function SalvaCategoriaListini() {
    let nuovaCategoria = document.getElementById("nuova_categoria").value;
    let id_parent = document.getElementById("id_parent").value;

    if (nuovaCategoria != "") {
        DistruggiTreeListini();

        AjaxHelper.post("/private/Listini/AggiungiCategoria", {
            id_parent: id_parent,
            nuovaCategoria: nuovaCategoria,   // 👈 underscore come in PHP
        })
            .then(risp => {
                ChiudiModal();
                $('#category_tree_html').html(risp.data);
                RicostruisciTreeListini();
            })
            .catch(err => {
                console.error("❌ Errore:", err);
            });

    } else {
        $('#messaggio').html('<p class="text-danger">Il nome della categoria non può essere vuoto!</p>');
    }
}

function ModificaCategoriaListini(idCategoriaListino, nomeCategoria) {
    // Logica per modificare una categoria
    let Titolo = "Modifica Categoria: " + nomeCategoria;
    let body = `
        <input type="hidden" name="id_categoria" id="id_categoria" class="form-control" value="${idCategoriaListino}">
        <div class="col-md-12 mt-3 mb-3">
            <label for="" class="form-label">Nome Categoria <span class="text-danger">*</span></label>
            <input type="text" name="nuova_categoria" id="nuova_categoria" class="form-control" value="${nomeCategoria}">
        </div>
        <div class="col-md-12 mt-3 mb-3" id="messaggio">

        </div>
    `;
    let bottoni = `
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Chiudi</button>
        <button type="button" class="btn btn-success" onclick="EditCategoriaListini()">Salva</button>
    `;
    MostraModal(Titolo, body, bottoni);
}

function EditCategoriaListini() {
    let id_categoria = document.getElementById("id_categoria").value;
    let nuova_categoria = document.getElementById("nuova_categoria").value;

    if (nuova_categoria != "") {
        DistruggiTreeListini();
        AjaxHelper.post("/private/Listini/EditCategoria", {
            id_categoria: id_categoria,
            nuova_categoria: nuova_categoria
        })
            .then(risp => {
                console.log("✅ Risposta server:", risp);

                if (!risp.success) {
                    console.error("❌ Errore dal server:", risp.error, risp.trace);
                    return;
                }

                $('#category_tree_html').html(risp.data);
                RicostruisciTreeListini();
                ChiudiModal();
            })
            .catch(err => {
                console.error("❌ Errore:", err);
            });
    }
}

