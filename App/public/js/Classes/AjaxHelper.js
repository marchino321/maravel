class AjaxHelper {
    static init() {
        if (AjaxHelper._initialized) {
            console.warn("⚠️ AjaxHelper.init() già chiamato, skip.");
            return;
        }
        AjaxHelper._initialized = true;

        // intercetta tutti i form con classe ajax-form
        $(document).on("submit", "form.ajax-form", function (e) {
            e.preventDefault();

            const $form = $(this);
            const useAjax = $form.data("ajax") === true || $form.data("ajax") === "true";

            if (useAjax) {
                AjaxHelper.submitForm(this);
            } else {
                // submit tradizionale (redirect)
                console.log("↪️ Redirect tradizionale:", $form.attr("action"));
                this.submit();
            }
        });
    }

    static post(url, data = {}, options = {}) {
        return new Promise((resolve, reject) => {
            AjaxHelper.showLoader();

            const isFormData = data instanceof FormData;
            if (isFormData) {
                if (!data.has("csrf_token")) {
                    data.append("csrf_token", window.CSRF_TOKEN);
                }
            } else {
                if (!("csrf_token" in data)) {
                    data.csrf_token = window.CSRF_TOKEN;
                }
            }

            $.ajax({
                url: url,
                method: "POST",
                data: data,
                dataType: "json", // 👈 prova a forzarlo dopo
                processData: !isFormData,
                contentType: isFormData ? false : "application/x-www-form-urlencoded; charset=UTF-8",
                ...options,
                success: (response, status, xhr) => {
                    if (response.flash) {
                        AjaxHelper.showFlash(response.flash);
                    }
                    if (response.logs && Array.isArray(response.logs)) {
                        AjaxHelper.printLogs(response.logs);
                    }
                    if (response.data) {
                        AjaxHelper.printData(response.data);
                    }
                    
                    AjaxHelper.hideLoader();
                    try {
                        resolve(response);
                    } catch (e) {
                        console.error("❌ JSON parse fallito. Risposta raw:", xhr.responseText);
                        reject(e);
                    }
                },
                error: (xhr, status, error) => {
                    AjaxHelper.hideLoader();
                    console.error("❌ Errore AJAX:", { status, error, raw: xhr.responseText });
                    reject({ xhr, status, error });
                }
            });
        });
    }

    static get(url, data = {}, options = {}) {
        return new Promise((resolve, reject) => {
            AjaxHelper.showLoader();

            // aggiungi CSRF token se non presente
            if (!("csrf_token" in data)) {
                data.csrf_token = window.CSRF_TOKEN;
            }

            $.ajax({
                url: url,
                method: "GET",
                data: data,
                dataType: "json",
                ...options,
                success: (response) => {
                    AjaxHelper.hideLoader();

                    if (response.flash) {
                        AjaxHelper.showFlash(response.flash);
                    }
                    if (response.logs && Array.isArray(response.logs)) {
                        AjaxHelper.printLogs(response.logs);
                    }
                    if (response.data) {
                        AjaxHelper.printData(response.data);
                    }
                    
                    resolve(response);
                },
                error: (xhr, status, error) => {
                    AjaxHelper.hideLoader();
                    reject({ xhr, status, error });
                }
            });
        });
    }

    // ----------------------
    // Wrapper per i form
    // ----------------------
    static submitForm(form, options = {}) {
        const $form = $(form);
        const useAjax = $form.data("ajax") === true || $form.data("ajax") === "true";
        let data;

        const hasFile = $form.find('input[type="file"]').length > 0;
        if (hasFile) {
            data = new FormData($form[0]);
        } else {
            data = $form.serialize();
        }

        if (!useAjax) {
            // 👉 Lascia al browser la gestione (header Location funziona)
            console.log("↪️ Redirect tradizionale:", $form.attr("action"));
            form.submit();
            return;
        }

        // Barra di caricamento
        AjaxHelper.showProgressBar();

        // Submit via AJAX
        return AjaxHelper.post($form.attr("action"), data, {
            method: $form.attr("method") || "POST",
            processData: !(data instanceof FormData),
            contentType: (data instanceof FormData) ? false : "application/x-www-form-urlencoded; charset=UTF-8",
            xhr: function () {
                const xhr = new window.XMLHttpRequest();
                xhr.upload.addEventListener("progress", (evt) => {
                    if (evt.lengthComputable) {
                        const percent = Math.round((evt.loaded / evt.total) * 100);
                        AjaxHelper.updateProgressBar(percent);
                    }
                }, false);
                return xhr;
            },
            ...options
        }).then(response => {
            // 🔑 se nel JSON c’è redirect → fai redirect da JS
            if (response.redirect) {
                setTimeout(() => {
                    window.location.href = response.redirect;
                }, 1000); // 1.5s per far comparire il toast
                //window.location.href = response.redirect;
            }
            return response;
        }).finally(() => {
            AjaxHelper.hideProgressBar();
        });
    }
    static actionForm(form, options = {}) {
        const $form = $(form);
        let data;

        // Se il form ha enctype multipart -> usa FormData
        const hasFile = $form.find('input[type="file"]').length > 0;
        if (hasFile) {
            data = new FormData($form[0]);
        } else {
            data = $form.serialize();
        }

        return AjaxHelper.post($form.attr("action"), data, {
            method: $form.attr("method") || "POST",
            processData: !(data instanceof FormData),
            contentType: (data instanceof FormData) ? false : "application/x-www-form-urlencoded; charset=UTF-8",
            ...options
        }).then(risp => {
            if (risp && risp.success) {
                return risp.data;   // 🔹 ritorna solo i dati utili
            } else {
                return Promise.reject(risp.error || "Errore sconosciuto");
            }
        });
    }
    // ----------------------
    // Funzioni di supporto
    // ----------------------
    static printLogs(logs) {
        logs.forEach(log => {
            const style = `color:${log.color}; font-weight:bold;`;
            console.log(`%c${log.emoji} ${log.tag} ${log.message}`, style);
        });
    }

    static printData(data) {
        if (data && Object.keys(data).length > 0) {
            console.group("📦 Dati restituiti dal server");
            console.log(data);
            console.groupEnd();
        }
    }

    static showFlash(messages) {
        if (!Array.isArray(messages)) {
            try {
                messages = JSON.parse(messages);
            } catch (e) {
                console.warn("⚠️ Flash non è un array né JSON valido:", messages);
                return;
            }
        }

        messages.forEach(msg => {
            let alertType = (msg.type || "info").toLowerCase();

            if (!["success", "info", "warning", "error"].includes(alertType)) {
                console.warn(`⚠️ Tipo toastr non valido: '${alertType}', fallback a info.`);
                alertType = "info";
            }

            let title = msg.title || "";
            let body = msg.body || "";

            toastr[alertType](body, title);
        });
    }

    static showLoader() {
        $("#loading").fadeIn("fast");
    }

    static hideLoader() {
        AjaxHelper.hideProgressBar();
        setTimeout(() => {
            $("#loading").fadeOut("slow", function () {
                $('#loading').hide();
            });
        }, 1000);
    }

    static showProgressBar() {
        if ($("#ajax-progress").length === 0) {
            $("body").append(`
                <div id="ajax-progress" style="
                    position: fixed;
                    top: 0; left: 0;
                    width: 100%;
                    height: 4px;
                    background: rgba(0,0,0,0.1);
                    z-index: 9999;">
                    <div id="ajax-progress-bar" style="
                        height: 100%;
                        width: 0;
                        background: #0d6efd;
                        transition: width 0.2s ease;">
                    </div>
                </div>
            `);
        }
        $("#ajax-progress-bar").css("width", "0%");
        $("#ajax-progress").fadeIn("fast");
    }

    static updateProgressBar(percent) {
        $("#ajax-progress-bar").css("width", percent + "%");
    }

    static hideProgressBar() {
        setTimeout(() => {
            $("#ajax-progress").fadeOut("slow", function () {
                $("#ajax-progress-bar").css("width", "0%");
            });
        }, 500);
    }
    
}

// 🔹 inizializza intercettore globale
$(document).ready(() => AjaxHelper.init());