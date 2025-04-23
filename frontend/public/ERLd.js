(function (){
    window.ERssoHandler = function () {

        const url = 'https://gsso.seguroselroble.com';
        const storagePrefix = 'ERSSO_';

        function getStorage(item) {
            let tmp = localStorage.getItem(storagePrefix + item);
            if (tmp) {
                try {
                    tmp = JSON.parse(tmp);
                    return tmp;
                }
                catch (e) {
                    return false;
                }
            }
        }
        function setStorage(item, data) {
            localStorage.setItem(storagePrefix + item, JSON.stringify(data));
        }

        function loadStyles() {

            const link = document.createElement( "link" );
            link.href = url + '/ERld.css';
            link.type = "text/css";
            link.rel = "stylesheet";
            document.getElementsByTagName( "head" )[0].appendChild( link );

            const loading = document.createElement('div');
            loading.className = 'loadingBox';
            loading.innerText = 'Iniciando sesión'

            const loadingContainer = document.createElement('div');
            loadingContainer.className = 'ERSSO_loading';
            loadingContainer.appendChild(loading);
            document.body.appendChild(loadingContainer);
        }

        this.checkAuth = function () {

            const windowTmp= window.open(url + '/#/auth/manager','name','height=500,width=600,top=250, left=600');

            window.onmessage = function (e) {
                //console.log(e);
                if (typeof e.data.k !== 'undefined' && e.data.k === 'ERSSO_POST_MSG') {
                    if (typeof window.ERSSO.c === 'function') {
                        window.ERSSO.c(e.data);
                    }
                    setStorage('auth', e.data);
                    windowTmp.close();
                }
            };

            /*let authData = getStorage('auth');

            if (!authData) {


            }
            else {
                if (typeof authData.token !== 'undefined') {
                    if (typeof window.ERSSO.c === 'function') {
                        window.ERSSO.c(authData);
                    }
                }
                /!*fetch(url + '/api/sso/auth/validate', {
                    method: "POST",
                    body: JSON.stringify({
                        a: window.ERSSO.t
                    }),
                    headers: {
                        "Content-Type": "application/json",
                        'Access-Control-Allow-Origin': '*'
                    },
                })
                    .then(response => response.json())
                    .then(response => {

                    })
                    .catch(() => {
                        //console.log(e);
                        //store.dispatch('coreHideLoading');
                        //window.location.href = "/#/login";
                    });*!/
            }*/
        }

        loadStyles();
    }
    if (typeof window.ERSSO === 'object') {
        window.ERSSO.h = new window.ERssoHandler();
        window.ERSSO.h.checkAuth();
    }
})();

