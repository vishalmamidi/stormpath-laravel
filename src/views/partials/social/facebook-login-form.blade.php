<button class="btn btn-social btn-facebook" onClick="facebookLogin()"> {{ config('stormpath.web.socialProviders.facebook.name') }}</button>


<script type="text/javascript">
    function facebookLogin() {
        var FB = window.FB;
        var facebookScopes = [];
        var scopes = [];

        if (facebookScopes.length) {
            scopes = facebookScopes.join(',');
        } else {
            scopes = '';
        }

        FB.login(function (response) {
            if (response.status === 'connected') {
                var queryStr = window.location.search.replace('?', '');
                // TODO make dynamic
                if (queryStr) {
                    window.location.replace('/callbacks/facebook?' + queryStr + '&access_token=' + response.authResponse.accessToken);
                } else {
                    window.location.replace('/callbacks/facebook?access_token=' + response.authResponse.accessToken);
                }
            }
        }, {scope: 'email' + (scopes ? ',' + scopes : '')});
    }

    window.fbAsyncInit = function () {
        FB.init({
            appId      : '{{ config('stormpath.web.socialProviders.facebook.clientId') }}',
            cookie     : true,
            xfbml      : true,
            version    : 'v2.3'
        });
    };

    (function (d, s, id){
        var js, fjs = d.getElementsByTagName(s)[0];
        if (d.getElementById(id)) {return;}
        js = d.createElement(s); js.id = id;
        js.src = "//connect.facebook.net/en_US/sdk.js";
        fjs.parentNode.insertBefore(js, fjs);
    }(document, 'script', 'facebook-jssdk'));
</script>