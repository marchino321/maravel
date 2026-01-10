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
        <button type="button" class="btn btn-success" onclick="SalvaCategoria()">Salva</button>
    `;
    MostraModal(Titolo, body, bottoni);
}

function SalvaCategoria() {
    let nuovaCategoria = document.getElementById("nuova_categoria").value;
    let id_parent = document.getElementById("id_parent").value;
    console.log(nuovaCategoria, id_parent);


    if (nuovaCategoria != "") {
        DistruggiTree();
        // Logica per salvare la categoria
        $('#messaggio').html("");

        AjaxHelper.post("/private/StrumentiAjax/AddCategoriaStrumento", {
            id_parent: id_parent, 
            nuova_categoria: nuovaCategoria, 
            csrf_token: window.CSRF_TOKEN // 👈 aggiungi il token
            })
            .then(risp => {
                ChiudiModal();
                $('#categorie_strumenti').html(risp.data);
                RicostruisciTree();
            })
            .catch(err => {
                console.error("❌ Errore:", err);
            });

    } else {
        $('#messaggio').html('<p class="text-danger">Il nome della categoria non può essere vuoto!</p>');
    }
}

function DistruggiTree() {
    $('#TreeStrumenti').jstree("destroy").empty();
}

function RicostruisciTree() {
    $("#TreeStrumenti").jstree({
        core: {
            themes: {
                responsive: !1
            }
        }, types: {
            default: {
                icon: "mdi mdi-food-drumstick"
            },
            file: {
                icon: "mdi mdi-food-drumstick-outline"
            }
        },
        plugins: ["types", "state"]
    });
}

function ModificaCategoriaStrumenti(idCategoriaStrumento, nomeCategoria) {
    let Titolo = "Modifica la categoria: " + nomeCategoria;
    let body = `
    <input type="hidden" name="id_categoria" id="id_categoria" class="form-control" value="${idCategoriaStrumento}">
        <div class="col-md-12 mt-3 mb-3">
            <label for="nuova_categoria" class="form-label">Nome Categoria <span class="text-danger">*</span></label>
            <input type="text" name="nuova_categoria" id="nuova_categoria" class="form-control" value="${nomeCategoria}" >
        </div>
        <div class="col-md-12 mt-3 mb-3" id="messaggio">
            
        </div>
    `;
    let bottoni = `
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Chiudi</button>
        <button type="button" class="btn btn-success" onclick="EditCategoria()">Salva</button>
    `;
    MostraModal(Titolo, body, bottoni);
}

function EditCategoria() {
    let nuovaCategoria = document.getElementById("nuova_categoria").value;
    let id_categoria = document.getElementById("id_categoria").value;
    if (nuovaCategoria != "") {
        DistruggiTree();

        AjaxHelper.post("/private/StrumentiAjax/EditCategoria", { nuovaCategoria: nuovaCategoria, id_categoria: id_categoria })
            .then(risp => {
                ChiudiModal();
                $('#categorie_strumenti').html(risp.data);
                RicostruisciTree();
            })
            .catch(err => {
                console.error("❌ Errore:", err);
            });

        $('#messaggio').html('');
    } else {
        $('#messaggio').html('<p class="text-danger">Il nome della categoria non può essere vuoto!</p>');
    }
}

//

$(document).ready(function () {

    $("#TreeStrumenti").jstree({
        core: {
            themes: {
                responsive: !1
            }
        },
        types: {
            default: {
                icon: "mdi mdi-food-drumstick"
            },
            file: {
                icon: "mdi mdi-food-drumstick-outline"
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
