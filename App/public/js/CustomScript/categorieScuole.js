$(document).ready(() => {
    RicostruisciTreeScuole();
})
function DistruggiTreeScuole() {
    $('#TreeScuole').jstree("destroy").empty();
}

function RicostruisciTreeScuole() {
    $("#TreeScuole").jstree({
        core: {
            themes: {
                responsive: !1
            }
        },

        types: {
            default: {
                icon: "fas fa-school"
            },
            file: { icon: "fas fa-graduation-cap" }
        },
        plugins: ["types", "state"],

    });
}
//classe_

document.addEventListener("click", function (e) {
    if (e.target.closest(".classi")) {
        e.preventDefault();
        let el = e.target.closest(".classi");
        let id = el.id;
        let text = el.textContent.trim();
        $('#idClasseStudente').val(id.match(/\d+/)[0]);
        
    }
});

function AggiungiCategoriaScuola(parentId, nomeCategoria) {
    // Logica per aggiungere una categoria
    let Titolo = "Aggiungi una class alla Scuola: " + nomeCategoria;
    
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
        <button type="button" class="btn btn-success" onclick="AggiungiClasse()">Salva</button>
    `;
    MostraModal(Titolo, body, bottoni);
}

function AggiungiClasse() {
    let nuovaCategoria = document.getElementById("nuova_categoria").value;
    let id_parent = document.getElementById("id_parent").value;
    if (nuovaCategoria != "") {
        DistruggiTreeScuole();
        $('#messaggio').html('');

        AjaxHelper.post("/private/Clienti/AggiungiClasse", {
            id_parent: id_parent, 
            nuova_categoria: nuovaCategoria
        })
            .then(risp => {
                //console.log("✅ Risposta server:", risp);

                if (!risp.success) {
                    console.error("❌ Errore dal server:", risp.error, risp.trace);
                    return;
                }

                ChiudiModal();
                $('#categoria_scuole').html(risp.data);
                RicostruisciTreeScuole();
            })
            .catch(err => {
                console.error("❌ Errore:", err);
            });

    } else {
        $('#messaggio').html('<p class="text-danger">Il nome della classe non può essere vuoto!</p>');
    }
    console.log("Salva classe:", nuovaCategoria);
    // Logica per salvare la categoria
}

function AggiungiScuola(){
    // Logica per aggiungere una categoria
    let Titolo = "Aggiungi una Scuola";
   
    let body = `
    <input type="hidden" name="id_parent" id="id_parent" class="form-control" value="0">
        <div class="col-md-12 mt-3 mb-3">
            <label for="" class="form-label">Nome Scuola <span class="text-danger">*</span></label>
            <input type="text" name="nuova_categoria" id="nuova_categoria" class="form-control" >
        </div>
        <div class="col-md-12 mt-3 mb-3" id="messaggio">
            
        </div>
    `;
    let bottoni = `
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Chiudi</button>
        <button type="button" class="btn btn-success" onclick="SalvaNuovaScuola()">Salva</button>
    `;
    MostraModal(Titolo, body, bottoni);
}

function SalvaNuovaScuola(){
    let nuovaCategoria = document.getElementById("nuova_categoria").value;
    //let id_parent = document.getElementById("id_parent").value;
    if (nuovaCategoria != "") {
        DistruggiTreeScuole();
        $('#messaggio').html('');

        AjaxHelper.post("/private/Clienti/AggiungiScuola", {
            //id_parent: id_parent, 
            nuova_categoria: nuovaCategoria
        })
            .then(risp => {
                //console.log("✅ Risposta server:", risp);

                if (!risp.success) {
                    console.error("❌ Errore dal server:", risp.error, risp.trace);
                    return;
                }

                ChiudiModal();
                $('#categoria_scuole').html(risp.data);
                RicostruisciTreeScuole();
                
            })
            .catch(err => {
                console.error("❌ Errore:", err);
            });

    } else {
        $('#messaggio').html('<p class="text-danger">Il nome della scuola non può essere vuoto!</p>');
    }
    console.log("Salva scuola:", nuovaCategoria);
}
