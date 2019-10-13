<?php

/**
 * CFCRedirect.create API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 * @return void
 * @see https://docs.civicrm.org/dev/en/latest/framework/api-architecture/
 */
function _civicrm_api3_c_f_c_redirect_create_spec(&$spec) {
  $spec['entity_id']['api.required'] = 1;
  $spec['post_id']['api.required'] = 1;
  $spec['page_type']['api.required'] = 1;
}

/**
 * CFCRedirect.create API
 *
 * @param array $params
 * @return array API result descriptor
 * @throws API_Exception
 */
function civicrm_api3_c_f_c_redirect_create($params) {
  $request = new WP_REST_Request('POST', '/cfcr-api/v2/r');

  array_map(
    function($field, $value) use ($request) {
      if (in_array($field, ['id', 'entity_id', 'post_id', 'page_type', 'is_active'])) {
        $request->set_param($field, $value);
      }
    },
    array_keys($params),
    $params
  );

  $response = rest_do_request($request);

  return civicrm_api3_create_success(
    [$response->get_data()],
    $params,
    'CFCRedirect',
    'create'
  );
}

/**
 * CFCRedirect.delete API
 *
 * @param array $params
 * @return array API result descriptor
 * @throws API_Exception
 */
function civicrm_api3_c_f_c_redirect_delete($params) {
  $request = new WP_REST_Request('DELETE', '/cfcr-api/v2/r');
  $request->set_param('id', $params['id']);

  $response = rest_do_request($request);

  return civicrm_api3_create_success(
    [$response->get_data()],
    $params,
    'CFCRedirect',
    'delete'
  );
}

/**
 * CFCRedirect.get API
 *
 * @param array $params
 * @return array API result descriptor
 * @throws API_Exception
 */
function civicrm_api3_c_f_c_redirect_get($params) {
  if (!empty($params['id'])) {
    $route = sprintf('/cfcr-api/v2/r/%d', $params['id']);
  } elseif (!empty($params['entity_id'])) {
    $route = sprintf('/cfcr-api/v2/r/entity/%d', $params['entity_id']);
  } else {
    $route = '/cfcr-api/v2/r';
  }

  $request = new WP_REST_Request('GET', $route);

  if (isset($params['page_type'])) {
    $request->set_param('page_type', $params['page_type']);
  }

  $response = rest_do_request($request);

  return civicrm_api3_create_success(
    empty($params['id']) || empty($params['entity_id']) ? $response->get_data() : [$response->get_data()],
    $params,
    'CFCRedirect',
    'get'
  );
}
