$(document).ready(function () {
    let checkCookies = getCookie("tutorial");
    if (checkCookies !== "done"){
        hopscotch.startTour({
            id: "my-intro",
            steps: [
                {
                    target: "nome_servizio",
                    title: "Nome Servizio",
                    content: "Qui dovrai inserire il nome del Servizio",
                    placement: "bottom",
                    yOffset: 10,
                    xOffset: -105,
                    arrowOffset: "center"
                },
                {
                    target: "durataMinima",
                    title: "Durata Minima",
                    content: "Qui dovrai inserire la durata minima del servizio",
                    placement: "top",
                    zindex: 9999
                },
                {
                    target: "tipologia",
                    title: "Tipologia",
                    content: "Qui dovrai selezionare la tipologia del servizio",
                    placement: "bottom",
                    zindex: 999
                },
                {
                    target: "uploadAppendici",
                    title: "Appendici Contratto",
                    content: "Qui dovrai caricare eventuali appendici del contratto",
                    placement: "bottom",
                    zindex: 999

                },
                {
                    target: "TreeServizi",
                    title: "Categorie Servizi",
                    content: "Qui dovrai selezionare le categorie del servizio",
                    placement: "bottom",
                    zindex: 999,
                    onNext: function () {

                        var triggerEl = document.querySelector('a[href="#finaziaria"][data-bs-toggle="tab"]');
                        if (triggerEl) {
                            var tab = new bootstrap.Tab(triggerEl);
                            tab.show();
                        }
                    }
                },
                {
                    target: "entita_giuridica",
                    title: "Entità Giuridica",
                    content: "Qui potrai scegliere tra tutte le Entità Giuridiche associate al tuo Studio",
                    placement: "bottom",
                    zindex: 999

                },
                {
                    target: "contratto_modello",
                    title: "Contratto Modello",
                    content: "Qui potrai scegliere il modello di contratto da utilizzare, sotto avrai tutti i dettagli",
                    placement: "bottom",
                    zindex: 999

                },
                {
                    target: "TreeListiniHtml",
                    title: "Scegli la categoria",
                    content: "Qui potrai scegliere la categoria del per popolare la lista dei Listini",
                    placement: "bottom",
                    zindex: 999,
                    onShow: function () {

                        var triggerEl = document.querySelector('select[id="listino_id"]');
                        if (triggerEl) {
                            triggerEl.focus();
                        }
                    },
                    onNext: function () {

                        var triggerEl = document.querySelector('a[href="#strumenti"][data-bs-toggle="tab"]');
                        if (triggerEl) {
                            var tab = new bootstrap.Tab(triggerEl);
                            tab.show();
                        }
                    }

                },
                {
                    target: "TreeStrumentiHtml",
                    title: "Scelta Categoria",
                    content: "Per prima cosa si sceglie la categoria, poi lo strumento e la quantità. Cliccando sul pulsante + si aggiunge una nuova riga, che funziona nello stesso modo: anche lì bisogna selezionare categoria, strumento e quantità. In pratica, l’albero delle categorie controlla sempre l’ultima riga inserita",
                    placement: "bottom",
                    zindex: 999,
                    onNext: function () {

                        var triggerEl = document.querySelector('a[href="#room"][data-bs-toggle="tab"]');
                        if (triggerEl) {
                            var tab = new bootstrap.Tab(triggerEl);
                            tab.show();
                        }
                    }

                },
                {
                    target: "room",
                    title: "Elenco Room",
                    content: "Qui trovi tutte le Room inserite che vuoi vincolare ad un servizio",
                    placement: "top",
                    zindex: 999,
                    onNext: function () {

                        var triggerEl = document.querySelector('a[href="#insegnanti"][data-bs-toggle="tab"]');
                        if (triggerEl) {
                            var tab = new bootstrap.Tab(triggerEl);
                            tab.show();
                        }
                    }

                },
                {
                    target: "insegnanti",
                    title: "Insegnanti",
                    content: "Qui potrai scegliere tra tutti gli Insegnanti associati al tuo Studio",
                    placement: "top",
                    zindex: 999,
                    onNext: function () {

                        var triggerEl = document.querySelector('a[href="#anagrafica-sala"][data-bs-toggle="tab"]');
                        if (triggerEl) {
                            var tab = new bootstrap.Tab(triggerEl);
                            tab.show();
                        }
                    }

                },
                {
                    target: "tipologia",
                    title: "Grazie",
                    content: "Grazie per avermi ascoltato spero di esserm espresso bene!",
                    placement: "top",
                    zindex: 999,
                    onShow: function () {

                        setCookie("tutorial", "done", 365);
                    },


                },

            ],
            showPrevButton: true,
        });
    }

});