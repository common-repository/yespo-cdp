class YespoExportData {
    constructor() {
        this.h1 = yespoVars.h1;
        this.outSideText = yespoVars.outSideText;
        this.h4 = yespoVars.h4;
        this.resume = yespoVars.resume;
        this.error = yespoVars.error;
        this.error401 = yespoVars.error401;
        this.error555 = yespoVars.error555;
        this.success = yespoVars.success;
        this.synhStarted = yespoVars.synhStarted;
        this.pluginUrl = yespoVars.pluginUrl;
        this.pauseButton = yespoVars.pauseButton;
        this.resumeButton = yespoVars.resumeButton;
        this.contactSupportButton = yespoVars.contactSupportButton;
        this.ajaxUrl = yespoVars.ajaxUrl;

        this.startExportUsersNonce = yespoVars.startExportUsersNonce;
        this.startExportOrdersNonce = yespoVars.startExportOrdersNonce;

        this.yespoGetAccountYespoNameNonce = yespoVars.yespoGetAccountYespoNameNonce;
        this.yespoCheckApiAuthorizationYespoNonce = yespoVars.yespoCheckApiAuthorizationYespoNonce;
        this.yespoGetUsersTotalNonce = yespoVars.yespoGetUsersTotalNonce;
        this.yespoGetUsersTotalExportNonce = yespoVars.yespoGetUsersTotalExportNonce;
        this.yespoGetProcessExportUsersDataToEsputnikNonce = yespoVars.yespoGetProcessExportUsersDataToEsputnikNonce;

        this.yespoGetOrdersTotalNonce = yespoVars.yespoGetOrdersTotalNonce;
        this.yespoGetOrdersTotalExportNonce = yespoVars.yespoGetOrdersTotalExportNonce;
        this.yespoGetProcessExportOrdersDataToEsputnikNonce = yespoVars.yespoGetProcessExportOrdersDataToEsputnikNonce;
        this.yespoStopExportDataToYespoNonce = yespoVars.yespoStopExportDataToYespoNonce;
        this.yespoResumeExportDataNonce = yespoVars.yespoResumeExportDataNonce;

        this.nonceApiKeyForm = yespoVars.nonceApiKeyForm;
        this.apiKeyValue = yespoVars.apiKeyValue;
        this.apiKeyText = yespoVars.apiKeyText + ' ';
        this.yespoLink = yespoVars.yespoLink;
        this.yespoLinkText = yespoVars.yespoLinkText;

        this.yespoApiKey = yespoVars.yespoApiKey;
        this.synchronize = yespoVars.synchronize;

        this.eventSource = null;
        this.percentTransfered = 0;

        this.users = null;
        this.usersExportStatus = false;
        this.orders = null;

        this.getAccountYespoName();
        this.checkAuthorization();
    }

    // get top account name
    getAccountYespoName(){
        this.getRequest('yespo_get_account_yespo_name', 'yespo_get_account_yespo_name_nonce', this.yespoGetAccountYespoNameNonce, (response) => {
            response = JSON.parse(response);
            if(document.querySelector('.panelUser') && response.username !== undefined) document.querySelector('.panelUser').innerHTML=response.username;
        });
    }

    /**
     * check authomatic authorization
     **/
    checkAuthorization(){
        this.getRequest('yespo_check_api_authorization_yespo', 'yespo_check_api_authorization_yespo_nonce', this.yespoCheckApiAuthorizationYespoNonce,  (response) => {
            response = JSON.parse(response);
            if(response.success && response.data.auth && response.data.auth === 'success'){
                this.getNumberDataExport();
            } else if(response.data.auth && response.data.auth === 'incorrect') {
                let code = 401;
                if(parseInt(response.data.code) === 0) code = 555;
                this.showErrorPage('', code);
            } else {
                this.showApiKeyForm();
                this.startExportEventListener();
            }
        });
    }
    /*
    * Methods create html elements
    * */
    createElement(tag, options = {}, ...children) {
        const element = document.createElement(tag);
        for (const [key, value] of Object.entries(options)) {
            if (key.startsWith("data-")) {
                element.setAttribute(key, value);
            } else {
                element[key] = value;
            }
        }
        children.forEach(child => {
            if (typeof child === "string") {
                element.appendChild(document.createTextNode(child));
            } else if (child instanceof Node) {
                element.appendChild(child);
            }
        });
        return element;
    }

    createHeading(level, text, options = {}) {
        return this.createElement(`h${level}`, options, text);
    }

    createParagraph(text, options = {}) {
        return this.createElement("p", options, text);
    }

    createFieldGroup(additionClass = '') {
        const classes = ["field-group"];
        if (additionClass) {
            classes.push(additionClass);
        }
        return this.createElement("div", { className: classes.join(' ') });
    }

    createInputField() {
        return this.createElement("input", { type: 'text', name: "yespo_api_key", id: "api_key", value: this.apiKeyValue });
    }

    createButton(id, className, text, iconSrc, iconClass) {
        const icon = this.createElement("img", { src: iconSrc, alt: `${id}-icon`, className: iconClass });
        const span = this.createElement("span", {}, text);
        return this.createElement("button", { type: "submit", id, className }, icon, span);
    }

    createForm(id, method, action) {
        return this.createElement("form", { id, method, action });
    }

    createProcessTitles(progressText, percentText, percentClass) {
        const processTitle = this.createElement("div", { className: "processTitle" }, progressText);
        const processPercent = this.createElement("div", { className: `processPercent ${percentClass}` }, percentText);
        return this.createElement("div", { className: "processTitles" }, processTitle, processPercent);
    }

    createProgressBar(containerId, barId, progressPercent) {
        const progressBar = this.createElement("div", {
            className: "progress-bar",
            id: barId,
            style: `width: ${progressPercent};`
        });
        return this.createElement("div", { className: "progress-container", id: containerId }, progressBar);
    }

    createMessageNonceError(nonce, icon, mesText, mesButton,imgSrc, text, resumeIconSrc, resumeText, contactIconSrc = null, contactText = null) {
        const img = this.createElement("img", { src: imgSrc, width: 24, height: 24, alt: "", title: "" });
        const messageIcon = this.createElement("div", { className: icon }, img);
        const messageText = this.createElement("div", { className: mesText }, text);

        const resumeButton = this.createButton("resume-send-data", "button button-primary", resumeText, resumeIconSrc, "button-icon");

        let contactSupport = '';
        if(contactIconSrc && contactText) {
            contactSupport = this.createElement("a", { id: "contact-support", className: "button btn-light", href: "https://yespo.io/support", target: "_blank" },
                this.createElement("img", { src: contactIconSrc, alt: "contact-icon", className: "contact-icon" }),
                this.createElement("span", {}, contactText)
            );
        }

        let messageButton = this.createElement("div", { className: mesButton }, contactSupport);
        if(resumeIconSrc && resumeText) messageButton = this.createElement("div", { className: mesButton }, contactSupport, resumeButton)

        return this.createElement("div", { className: nonce }, messageIcon, messageText, messageButton);
    }

    appendUserPanel(
        sectionClass,
        progressContainer,
        exportProgressBar,
        progressText,
        progressPercent,
        percentClass,
        stopped,
        code = null
    ){
        const settingsSection = '.settingsSection';
        const sectionBody = this.createElement('div', { className: 'sectionBody sectionBodyAuth' });
        const formBlock = this.createElement('div', { className: 'formBlock' });

        let formId = 'stopExportData';
        if (stopped === 'stopped') formId = 'resumeExportData';

        const form = this.createForm(formId, 'post', '');
        const h4 = this.createHeading(4, this.h4);

        const fieldGroup1 = this.createFieldGroup();
        const processTitles = this.createProcessTitles(progressText, progressPercent, percentClass);
        const progressBar = this.createProgressBar(progressContainer, exportProgressBar, progressPercent);

        fieldGroup1.appendChild(processTitles);
        fieldGroup1.appendChild(progressBar);

        const fieldGroup2 = this.createFieldGroup();
        let sectionContent = '';

        if (stopped === 'stopped') {
            sectionContent = this.createMessageNonceError(
                'messageNonce',
                'messageIcon',
                'messageText',
                'messageButton',
                this.pluginUrl + 'assets/images/esicon.svg',
                this.resume,
                this.pluginUrl + 'assets/images/union.svg',
                this.resumeButton

            );
        } else if (stopped === 'error') {

            let messageText = this.error;
            let resumeIcon = this.pluginUrl + 'assets/images/union.svg';
            let resumeButton = this.resumeButton;
            if(code === 401 || code === 555) {
                if(code === 401) messageText = this.error401;
                else messageText = this.error555;
                resumeIcon = '';
                resumeButton = '';
            }

            sectionContent = this.createMessageNonceError(
                'messageNonceError',
                'messageIconError',
                'messageTextError',
                'messageButtonError',
                this.pluginUrl + 'assets/images/erroricon.svg',
                messageText,
                resumeIcon,
                resumeButton,
                this.pluginUrl + 'assets/images/subtract.svg',
                code !== 555 ? this.contactSupportButton : null
            );
        } else {
            fieldGroup2.classList.add('flexRow');
            sectionContent = this.createElement('input', { type: 'submit', id: 'stop-send-data', className: 'button btn-light', value: this.pauseButton });
        }
        const mesSynhStarted = this.createElement("div", { className: 'synhronizationStarted' });
        fieldGroup2.appendChild(sectionContent);
        fieldGroup2.appendChild(mesSynhStarted);


        form.append(h4, fieldGroup1, fieldGroup2);
        formBlock.appendChild(form);
        sectionBody.appendChild(formBlock);

        const mainContainer = document.querySelector(settingsSection);
        if (mainContainer) {
            mainContainer.innerHTML = '';
            mainContainer.appendChild(sectionBody);
        }
    }

    addSuccessMessage() {
        const sectionBody = this.createElement('div', { className: 'sectionBody sectionBodySuccess' });
        const formBlock = this.createElement('div', { className: 'formBlock' });
        const fieldGroup = this.createElement('div', { className: 'field-group' });
        const messageNonceSuccess = this.createElement('div', { className: 'messageNonceSuccess' });
        const messageIconSuccess = this.createElement('div', { className: 'messageIconSuccess' });

        const img = this.createElement('img', {
            src: this.pluginUrl + 'assets/images/success.svg',
            width: 24,
            height: 24,
            alt: 'success',
            title: 'success'
        });

        messageIconSuccess.appendChild(img);

        const messageTextSuccess = this.createElement('div', { className: 'messageTextSuccess' }, this.success );

        messageNonceSuccess.appendChild(messageIconSuccess);
        messageNonceSuccess.appendChild(messageTextSuccess);

        fieldGroup.appendChild(messageNonceSuccess);
        formBlock.appendChild(fieldGroup);
        sectionBody.appendChild(formBlock);

        const messageContainer = document.querySelector('.settingsSection');
        if (messageContainer) {
            messageContainer.innerHTML = '';
            messageContainer.appendChild(sectionBody);
        }
    }

    /**
     * AUTHORIZATION FORM **/
    showApiKeyForm() {
        const sectionBody = this.createElement('div', { className: 'sectionBody sectionBodyAuth' });
        const formBlock = this.createElement('div', { className: 'formBlock' });

        let formId = 'checkYespoAuthorization';

        const form = this.createForm(formId, 'post', '');
        const h4 = this.createHeading(4, this.yespoApiKey);

        const fieldGroup0 = this.createFieldGroup();
        const inputApiLine = this.createElement("div", { className: 'inputApiLine' });
        const inputField = this.createInputField();
        const errorAuth = this.createElement("div", { className: 'sendYespoAuthData' });

        inputApiLine.appendChild(inputField);
        inputApiLine.appendChild(errorAuth);
        fieldGroup0.appendChild(inputApiLine);

        const fieldGroup1 = this.createFieldGroup();
        const divEl = this.createElement("div", { className: 'informationText' });
        const spanEl = this.createElement("span", { className: 'api-key-text' }, this.apiKeyText);
        const aEl = this.createElement("a", { href: this.yespoLink }, this.yespoLinkText);
        divEl.appendChild(spanEl);
        divEl.appendChild(aEl);
        fieldGroup1.appendChild(divEl);

        const nonceField = this.createElement('div', { id: 'nonceField' });
        nonceField.innerHTML = this.nonceApiKeyForm;

        const fieldGroup2 = this.createFieldGroup();

        const submitButton = this.createElement('input', { type: 'submit', id: 'sendYespoAuthData', className: 'button button-primary', value: this.synchronize });
        fieldGroup2.appendChild(submitButton);

        form.append(h4, fieldGroup0, fieldGroup1, nonceField, fieldGroup2);
        formBlock.appendChild(form);
        sectionBody.appendChild(formBlock);

        const mainContainer = document.querySelector('.settingsSection');
        if (mainContainer) {
            mainContainer.innerHTML = '';
            mainContainer.appendChild(sectionBody);
        }
    }
    /*
    * Methods dealing export data
    * */
    eventListeners(){
        document.addEventListener('DOMContentLoaded', () => {
            this.startExportEventListener();
        });
    }

    checkSynchronization(form){
        var spinner = document.getElementById('spinner');
        if (document.getElementById('sendYespoAuthData')) document.getElementById('sendYespoAuthData').disabled = true;
        var xhr = new XMLHttpRequest();
        xhr.open('POST', this.ajaxUrl, true);
        var formData = new FormData(form);
        formData.append('action', 'yespo_check_api_key_esputnik');
        document.querySelector('.sendYespoAuthData').innerHTML = '';
        xhr.send(formData);
        xhr.onreadystatechange = () => {
            if (xhr.readyState === XMLHttpRequest.DONE) {
                if (xhr.status === 200) {
                    var response = JSON.parse(xhr.responseText);
                    try {
                        if(response.status === 'success') {
                            if (document.querySelector('.panelUser') && response.username !== '' && response.username !== undefined) document.querySelector('.panelUser').innerHTML = response.username;
                            this.getNumberDataExport();
                        } else if(response.status && response.status === 'incorrect') {
                            let code = 401;
                            if(parseInt(response.code) === 0) code = 555;
                            this.showErrorPage('', code);
                        } else {
                            document.querySelector('.sendYespoAuthData').innerHTML = response.message;
                            if (document.getElementById('sendYespoAuthData')) document.getElementById('sendYespoAuthData').disabled = false;
                        }
                    } catch (error) {
                        console.error('Error:', error);
                    }
                }
            }
        };
    }

    transformationTotalResponse(response){
        let result = JSON.parse(response);
        if(result && result.export){
            return result.export;
        }
        return 0;
    }

    getNumberDataExport(){
        Promise.all([
            this.getRequest('yespo_get_users_total_export', 'yespo_get_users_total_export_nonce', this.yespoGetUsersTotalExportNonce, (response) => {
                this.users = JSON.parse(response);
            }),
            this.getRequest('yespo_get_orders_total_export', 'yespo_get_orders_total_export_nonce', this.yespoGetOrdersTotalExportNonce, (response) => {
                this.orders = JSON.parse(response);
            })
        ]).then(() => {
            this.route(this.users, this.orders);
            this.stopExportEventListener();
        });
    }

    getRequest(action, nonce, nonceValue, callback) {
        return new Promise((resolve, reject) => {
            let xhr = new XMLHttpRequest();
            xhr.open('GET', this.ajaxUrl + '?action=' + action + '&' + nonce + '=' + nonceValue, true);
            xhr.onreadystatechange = function () {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    let response = xhr.responseText;
                    if (response) {
                        callback(response);
                    }
                    resolve();
                }
            };
            xhr.send();
        });
    }

    route(users, orders){

        let total = parseInt(users.export) + parseInt(orders.export);
        let status = false;
        if(users.status || orders.status) status = true;
        if( parseInt(users.percent) < 100 ) this.percentTransfered = users.percent;
        else if( parseInt(orders.percent) < 100 ) this.percentTransfered = orders.percent;
        if(total > 0 && status) {
            this.stopExportData();
        } else if(total > 0) {
            this.startExportData();
            if(parseInt(users.export) > 0) this.startExportUsers();
            else if(parseInt(orders.export) > 0) this.startExportOrders();
        } else {
            this. addSuccessMessage();
        }
    }

    startExportData(){
        this.showExportProgress(this.percentTransfered);
        this.updateProgress(this.percentTransfered, 'export');
    }

    showExportProgress(percent){
        this.appendUserPanel(
            '.userPanel',
            'progressContainer',
            'exportProgressBar',
            this.h1,
            percent + '%',
            '',
            ''
        );
        if(document.querySelector('.synhronizationStarted')) document.querySelector('.synhronizationStarted').innerHTML=this.synhStarted;
    }
    startExportEventListener() {
        if(document.querySelector('#checkYespoAuthorization')) {
            let form = document.querySelector('#checkYespoAuthorization');
            form.addEventListener('submit', (event) => {
                event.preventDefault();
                this.checkSynchronization(form);
            });
        }
    }

    //STOP EXPORT
    stopExportData(){
        Promise.all([
            this.getRequest('yespo_stop_export_data_to_yespo', 'yespo_stop_export_data_to_yespo_nonce', this.yespoStopExportDataToYespoNonce, (response) => {
                this.percentTransfered = parseInt(response);
            })
        ]).then(() => {
            this.showResumeExportProgress(this.percentTransfered);
            this.resumeExportEventListener();
        });

    }
    stopExportEventListener(){
        if(document.querySelector('#stopExportData')) {
            document.querySelector('#stopExportData').addEventListener('submit', (event) => {
                event.preventDefault();
                document.querySelector('#stop-send-data').disabled = true;
                this.stopExportData();
            });
        }
    }

    //RESUME EXPORT
    showResumeExportProgress(percent){
        this.appendUserPanel(
            '.userPanel',
            'progressContainerStopped',
            'exportProgressBarStopped',
            this.h1,
            percent + '%',
            '',
            'stopped'
        );
    }

    resumeExportEventListener(){
        if(document.querySelector('#resumeExportData')) {
            document.querySelector('#resumeExportData').addEventListener('submit', (event) => {
                event.preventDefault();
                document.querySelector('#resume-send-data').disabled = true;
                this.resumeExportData();
            });
        }
    }

    resumeExportData(){
        Promise.all([
            this.getRequest('yespo_resume_export_data', 'yespo_resume_export_data_nonce', this.yespoResumeExportDataNonce, (response) => {
                this.percentTransfered = parseInt(response);
            })
        ]).then(() => {
            this.stopExportEventListener();
            this.getNumberDataExport();
            this.processExportUsers();
        });

    }

    //ERROR PAGE
    showErrorPage(percent, code) {
        this.appendUserPanel(
            '.userPanel',
            'progressContainerStopped',
            'exportProgressBarStopped',
            this.h1,
            percent,
            'percentRed',
            'error',
            parseInt(code)
        );
    }


    /**
     * start export
     * **/
    startExportUsers() {
        if(this.users.export > 0) {
            this.startExport(
                'yespo_export_user_data_to_esputnik',
                'users',
                'yespo_start_export_nonce',
                this.startExportUsersNonce
            );
        }
    }

    startExport(action, service, nonceName, nonceAction){
        this.startExportChunk(action, service, nonceName, nonceAction);
    }

    startExportOrders() {
        this.startExport(
            'yespo_export_order_data_to_esputnik',
            'orders',
            'yespo_start_export_orders_nonce',
            this.startExportOrdersNonce
        );
    }

    startExportChunk(action, service, nonceName, nonceAction) {
        const formData = new FormData();
        formData.append('service', service);
        formData.append('action', action);
        formData.append(nonceName, nonceAction);

        fetch(this.ajaxUrl, {
            method: 'POST',
            body: formData
        })
            .then(response => {
                if (response) {
                    if(service === 'users'){
                        this.processExportUsers();
                    }
                    if(service === 'orders'){
                        this.processExportOrders();
                    }
                } else console.error('Send error:', response.statusText);
            })
            .catch(error => {
                console.error('Send error:', error);
            });
    }

    /** check and update process **/

    processExportUsers() {
        this.checkExportStatus(
            'yespo_get_process_export_users_data_to_esputnik',
            'yespo_get_process_export_users_data_to_esputnik_nonce',
            this.yespoGetProcessExportUsersDataToEsputnikNonce,
            'users',
            '#total-users-export',
            'yespo_export_user_data_to_esputnik',
            '#progressContainerUsers',
            '#exported-users'
        );
    }

    processExportOrders() {
        this.checkExportStatus(
            'yespo_get_process_export_orders_data_to_esputnik',
            'yespo_get_process_export_orders_data_to_esputnik_nonce',
            this.yespoGetProcessExportOrdersDataToEsputnikNonce,
            'orders',
            '#total-orders-export',
            'export_orders_data_to_esputnik',
            '#progressContainerOrders',
            '#exported-orders'
        );
    }

    checkExportStatus(action, nonce, nonceValue, way, totalUnits, totalExport, progressUnits, exportedUnits) {
        this.getProcessData(action, nonce, nonceValue,(response) => {
            response = JSON.parse(response);
            if (response.exported !== null && response.exported >= 0) {
                this.updateProgress(Math.floor( (response.exported / response.total) * 100), 'export');
            }


            if( response.percent === 100 && way === 'users' && response.status === 'completed'){
                this.startExportOrders();
            } else if(response.percent === 100 && way === 'orders' && response.status === 'completed') this.updateProgress(100);

            if(response.code && parseInt(response.code) === 0){

                if(document.querySelector('.processPercent')) response.percent = document.querySelector('.processPercent').innerText;
                if(!document.querySelector('.messageIconError')) this.showErrorPage(response.percent, '555');
                setTimeout(() => {
                    if(way === 'users') this.processExportUsers();
                    if(way === 'orders') this.processExportOrders();
                }, 5000);

            } else if (response.exported !== response.total && response.status === 'active') {
                this.exportStatus = true;
                if(document.querySelector('.messageIconError')) this.resumeExportData();
                setTimeout(() => {
                    if(way === 'users') this.processExportUsers();
                    if(way === 'orders') this.processExportOrders();
                }, 5000);
            } else if(response.status === 'error'){
                if(document.querySelector('.processPercent')) response.percent = document.querySelector('.processPercent').innerText;
                this.showErrorPage(response.percent, response.code);
            } else {
                this.usersExportStatus = false;
            }
        });
    }

    getProcessData(action, nonce, nonceValue, callback){
        return new Promise((resolve, reject) => {
            let xhr = new XMLHttpRequest();
            xhr.open('GET', this.ajaxUrl + '?action=' + action + '&' + nonce + '=' + nonceValue, true);
            xhr.onreadystatechange = function () {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    let response = xhr.responseText;
                    if (response) {
                        callback(response);
                    }
                    resolve();
                } else if (xhr.status === 500) {
                    console.error('Internal Server Error:', xhr.responseText);
                    document.querySelector('.sendYespoAuthData').innerHTML = 'Internal Server Error. Please try again later.';
                    if (document.getElementById('sendYespoAuthData')) document.getElementById('sendYespoAuthData').disabled = false;
                }


            };
            xhr.send();
        });
    }


    updateProgress(progress, way) {

        let progressBar = null;
        if(way === 'export' && document.querySelector('#exportProgressBar')) progressBar = document.querySelector('#exportProgressBar');
        else if(document.querySelector('#exportProgressBarStopped')) progressBar = document.querySelector('#exportProgressBarStopped');

        if(document.querySelector('.processPercent ')) document.querySelector('.processPercent ').textContent = `${progress}%`;

        if(progressBar){
            progressBar.style.width = `${progress}%`;
            if(progress > 0){
                if(document.querySelector('.synhronizationStarted')) document.querySelector('.synhronizationStarted').innerHTML='';
            }
            if (progress >= 100) {

                if (this.eventSource) {
                    this.eventSource.close();
                }
                if( document.querySelector('#stop-send-data') ) document.querySelector('#stop-send-data').disabled = true;
                setTimeout(() => {
                    this. addSuccessMessage();
                }, 5000);
            }
        }
    }

}

new YespoExportData();