<?php

/**
 * Handle linkback() response from Poniverse.
 */

if (isset($_REQUEST['state'])) {
    $_REQUEST['AuthState'] = $_REQUEST['state'];
}

if (!array_key_exists('AuthState', $_REQUEST) || empty($_REQUEST['AuthState'])) {
    throw new SimpleSAML_Error_BadRequest('Missing state parameter on poniverse linkback endpoint.');
}

if (!array_key_exists('code', $_REQUEST) || empty($_REQUEST['code'])) {
    throw new SimpleSAML_Error_BadRequest('Missing code parameter on poniverse linkback endpoint.');
}

$state = SimpleSAML_Auth_State::loadState($_REQUEST['AuthState'], sspmod_authponiverse_Auth_Source_Poniverse::STAGE_INIT);

// Find authentication source
if (!array_key_exists(sspmod_authponiverse_Auth_Source_Poniverse::AUTHID, $state)) {
    throw new SimpleSAML_Error_BadRequest('No data in state for ' . sspmod_authponiverse_Auth_Source_Poniverse::AUTHID);
}
$sourceId = $state[sspmod_authponiverse_Auth_Source_Poniverse::AUTHID];

$source = SimpleSAML_Auth_Source::getById($sourceId);
if ($source === NULL) {
    throw new SimpleSAML_Error_BadRequest('Could not find authentication source with id ' . var_export($sourceId, TRUE));
}

$state['code'] = $_REQUEST['code'];

try {
    if (isset($_REQUEST['error_reason']) && $_REQUEST['error_reason'] == 'user_denied') {
        throw new SimpleSAML_Error_UserAborted();
    }

    $source->finalStep($state);
} catch (SimpleSAML_Error_Exception $e) {
    SimpleSAML_Auth_State::throwException($state, $e);
} catch (Exception $e) {
    SimpleSAML_Auth_State::throwException($state, new SimpleSAML_Error_AuthSource($sourceId, 'Error on poniverse linkback endpoint.', $e));
}

SimpleSAML_Auth_Source::completeAuth($state);
