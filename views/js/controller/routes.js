define(function(){
    return {
        'PlatformAdmin': {
            'css': 'auth-selector',
            'actions': {
                'addInstanceForm': 'controller/PlatformAdmin/editor',
                'editInstance': 'controller/PlatformAdmin/editor'
            }
        },
        'Publish': {
            'actions': {
                'selectRemoteEnvironments': 'controller/Publish/selectRemoteEnvironments',
                'selectClassRemoteEnvironments': 'controller/Publish/selectRemoteEnvironments',
            }
        }
    };
});
