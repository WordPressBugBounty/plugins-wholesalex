<?php
/**
 * Neutral registration provider boundary.
 *
 * @package WholesaleX
 */

namespace WHOLESALEX;

defined( 'ABSPATH' ) || exit;

/** Dispatches real field implementations without enabling base custom branches. */
final class Registration_Provider {
	/**
	 * Resolve the versioned callable provider.
	 *
	 * @return array|null
	 */
	public static function get() {
		$provider = apply_filters( 'wholesalex_registration_provider', null );
		if ( ! is_array( $provider ) || 1 !== ( $provider['api_version'] ?? null ) ) {
			return null;
		}
		foreach ( array( 'supports', 'render', 'validate_submission', 'validate_form' ) as $operation ) {
			if ( ! isset( $provider[ $operation ] ) || ! is_callable( $provider[ $operation ] ) ) {
				return null;
			}
		}
		if ( isset( $provider['after_create'] ) && ! is_callable( $provider['after_create'] ) ) {
			return null;
		}
		return $provider;
	}

	/**
	 * Stable saved-field identity; reading never invents IDs.
	 *
	 * @param array $field Saved definition.
	 * @return string
	 */
	public static function identity( $field ) {
		return isset( $field['id'] ) && is_scalar( $field['id'] ) && '' !== (string) $field['id']
			? 'id:' . (string) $field['id'] : 'name:' . ( is_string( $field['name'] ?? null ) ? $field['name'] : '' );
	}

	/**
	 * Provider unavailable/invalid data error.
	 *
	 * @return \WP_Error
	 */
	public static function unavailable() {
		return new \WP_Error( 'wholesalex_registration_provider_unavailable', __( 'This form requires an available registration field provider. Please contact the store administrator.', 'wholesalex' ), array( 'status' => 409 ) );
	}

	/**
	 * Test actual provider support.
	 *
	 * @param array $field Definition.
	 * @param array $context Server context.
	 * @return bool
	 */
	public static function supports( $field, $context = array() ) {
		$provider = self::get();
		return null !== $provider && true === call_user_func( $provider['supports'], $field, $context );
	}

	/**
	 * Render provider-owned markup only.
	 *
	 * @param array $field Definition.
	 * @param array $context Server context.
	 * @return string|\WP_Error
	 */
	public static function render( $field, $context ) {
		$provider = self::get();
		if ( null === $provider || true !== call_user_func( $provider['supports'], $field, $context ) ) {
			return self::unavailable();
		}
		$result = call_user_func( $provider['render'], $field, $context );
		return is_string( $result ) || is_wp_error( $result ) ? $result : self::unavailable();
	}

	/**
	 * Flatten saved definitions without trusting posted definitions.
	 *
	 * @param array $form Saved form.
	 * @param bool  $active_only Restrict to active registration fields.
	 * @return array
	 */
	public static function fields( $form, $active_only = false ) {
		$fields = array();
		foreach ( $form['registrationFields'] ?? array() as $row ) {
			foreach ( $row['columns'] ?? array() as $field ) {
				if ( is_array( $field ) && ( ! $active_only || ! empty( $field['status'] ) ) ) {
					$fields[] = $field;
				}
			}
		}
		return $fields;
	}

	/**
	 * Validate before customer creation and restrict returned persistence keys.
	 *
	 * @param array $fields Authoritative active definitions.
	 * @param array $values Posted values after nonce/context authorization.
	 * @param array $files Upload descriptors after nonce/context authorization.
	 * @param array $context Approved server context.
	 * @return array|\WP_Error Canonical meta/errors/files result.
	 */
	public static function submission( $fields, $values, $files, $context ) {
		$custom    = array();
		$owned     = array();
		$file_keys = array();
		foreach ( $fields as $field ) {
			if ( WholesaleX_CommonUtils::is_standard_registration_field( $field ) ) {
				continue;
			}
			if ( ! is_string( $field['name'] ?? null ) ) {
				return self::unavailable();
			}
			if ( ! self::supports( $field, $context ) ) {
				$name = $field['name'] ?? '';
				if ( empty( $field['required'] ) && ! array_key_exists( $name, $values ) && ! array_key_exists( $name, $files ) ) {
					continue;
				}
				return self::unavailable();
			}
			$name = $field['name'] ?? '';
			// Older builders generated hyphenated names; retain their saved persistence keys.
			if ( ! is_string( $name ) || ! preg_match( '/^[a-zA-Z0-9_-]{1,128}$/D', $name ) ) {
				return self::unavailable();
			}
			$key = ! empty( $field['migratedFromOldBuilder'] ) ? $name : 'wholesalex_cf_' . $name;
			if ( self::is_protected_meta_key( $name ) || self::is_protected_meta_key( $key ) || isset( $owned[ $key ] ) ) {
				return self::unavailable();
			}
			if ( 'file' === ( $field['type'] ?? '' ) ) {
				$file_keys[ ! empty( $field['migratedFromOldBuilder'] ) ? 'file_' . $name : $key ] = true;
			} else {
				$owned[ $key ] = true;
			}
			if ( ! empty( $field['migratedFromOldBuilder'] ) && 'file' === ( $field['type'] ?? '' ) ) {
				$file_keys[ 'file_' . $name ] = true;
			}
			$custom[] = $field;
		}
		$empty = array(
			'meta'   => array(),
			'errors' => array(),
			'files'  => array(),
		);
		if ( empty( $custom ) ) {
			return $empty;
		}
		$bounded_values = array();
		$bounded_files  = array();
		foreach ( $custom as $field ) {
			$name = $field['name'];
			if ( array_key_exists( $name, $values ) ) {
				$bounded_values[ $name ] = $values[ $name ];
			}
			if ( array_key_exists( $name, $files ) ) {
				$bounded_files[ $name ] = $files[ $name ];
			}
		}
		if ( strlen( wp_json_encode( $bounded_values ) ) > 65536 || count( $custom ) > 100 ) {
			return self::unavailable();
		}
		$provider = self::get();
		$result   = call_user_func( $provider['validate_submission'], $custom, $bounded_values, $bounded_files, $context );
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		if ( ! is_array( $result ) ) {
			return self::unavailable();
		}
		foreach ( $empty as $bucket => $ignored ) {
			if ( ! isset( $result[ $bucket ] ) || ! is_array( $result[ $bucket ] ) ) {
				return self::unavailable();
			}
		}
		if ( false === wp_json_encode( $result ) || strlen( wp_json_encode( $result ) ) > 131072 ) {
			return self::unavailable();
		}
		foreach ( $result['errors'] as $error ) {
			if ( ! is_string( $error ) ) {
				return self::unavailable();
			}
		}
		foreach ( array( 'meta', 'files' ) as $bucket ) {
			foreach ( $result[ $bucket ] as $key => $value ) {
				$keys = 'files' === $bucket ? $file_keys : $owned;
				if ( ! isset( $keys[ $key ] ) || self::is_protected_meta_key( $key ) ) {
					return self::unavailable();
				}
				if ( 'meta' === $bucket && ! is_scalar( $value ) && ! ( is_array( $value ) && count( $value ) <= 100 && count( array_filter( $value, 'is_scalar' ) ) === count( $value ) ) ) {
					return self::unavailable();
				}
				if ( 'files' === $bucket && ( ! is_array( $value ) || ! isset( $value['name'], $value['tmp_name'], $value['error'], $value['size'] ) || ! is_string( $value['name'] ) || ! is_string( $value['tmp_name'] ) || 0 !== $value['error'] || ! is_int( $value['size'] ) || $value['size'] <= 0 ) ) {
					return self::unavailable();
				}
			}
		}
		return array_intersect_key( $result, $empty );
	}

	/**
	 * Protected account/provenance keys never belong to custom persistence.
	 *
	 * @param string $key Meta key.
	 * @return bool
	 */
	public static function is_protected_meta_key( $key ) {
		global $wpdb;
		$prefix = isset( $wpdb->prefix ) && is_string( $wpdb->prefix ) ? $wpdb->prefix : 'wp_';
		return ( '' !== $prefix && 0 === strpos( $key, $prefix ) ) || 0 === strpos( $key, '_' ) || 0 === strpos( $key, 'wp_' ) || preg_match( '/(?:^|_)(?:capabilities|user_level)$/D', $key ) || in_array( $key, array( 'capabilities', 'user_level', 'session_tokens', 'user_login', 'user_pass', 'user_email', 'user_url', 'first_name', 'last_name', 'nickname', 'display_name', 'description', 'role', 'roles', 'password', 'user_confirm_pass', 'user_confirm_email', 'wholesalex_registration_role', 'wholesalex_registration_status', 'wholesalex_registration_form_context', 'wholesalex_confirmation_key' ), true );
	}

	/**
	 * Core writable field properties; extension properties remain opaque.
	 *
	 * @return array
	 */
	public static function standard_field_keys() {
		return array( 'id', 'name', 'type', 'custom_field', 'label', 'placeholder', 'required', 'status', 'isLabelHide', 'help_message', 'columnPosition', 'default_text', 'term_link', 'password_conditions', 'password_message', 'rows', 'cols', 'parent', 'excludeRoles', 'passwordStrength', 'password_strength_message' );
	}

	/**
	 * Complete current standard form presentation shape, including migrated defaults.
	 *
	 * @return array
	 */
	public static function base_form_shape() {
		$shape                                      = WholesaleX_CommonUtils::normalize_default_registration_template( WholesaleX_CommonUtils::get_empty_form() );
		$shape['settings']['isShowFormTitle']       = true;
		$shape['settings']['appearance_colorTheme'] = '';
		return $shape;
	}

	/**
	 * Preserve nested foreign object properties from storage, never from the client.
	 * Lists are domain values; their owning validator controls their contents.
	 *
	 * @param array $candidate Proposed object.
	 * @param array $saved Authoritative object.
	 * @param array $schema Base presentation shape.
	 * @return array
	 */
	public static function preserve_unknown( $candidate, $saved, $schema ) {
		foreach ( $saved as $key => $value ) {
			if ( is_string( $key ) && 0 === strpos( $key, 'appearance_' ) ) {
				$schema[ $key ] = $value;
			}
			if ( ! array_key_exists( $key, $schema ) ) {
				$candidate[ $key ] = $value;
			} elseif ( is_array( $value ) && is_array( $schema[ $key ] ) && ! empty( $schema[ $key ] ) && array_keys( $schema[ $key ] ) !== range( 0, count( $schema[ $key ] ) - 1 ) ) {
				$next              = isset( $candidate[ $key ] ) && is_array( $candidate[ $key ] ) ? $candidate[ $key ] : array();
				$candidate[ $key ] = self::preserve_unknown( $next, $value, $schema[ $key ] );
			}
		}
		return $candidate;
	}

	/**
	 * Row keys owned by the base form layout.
	 *
	 * @return array
	 */
	public static function standard_row_keys() {
		return array( 'id', 'type', 'columns', 'isMultiColumn' );
	}

	/**
	 * Stable row identity without inventing missing legacy IDs.
	 *
	 * @param array $row Stored row.
	 * @return string
	 */
	public static function row_identity( $row ) {
		if ( isset( $row['id'] ) && is_scalar( $row['id'] ) && '' !== (string) $row['id'] ) {
			return 'id:' . (string) $row['id'];
		}
		$fields = array_map( array( __CLASS__, 'identity' ), $row['columns'] ?? array() );
		sort( $fields, SORT_STRING );
		return 'fields:' . wp_json_encode( $fields );
	}

	/**
	 * Match an ID-less partial row only through its saved field identities.
	 *
	 * @param array $row Proposed row.
	 * @param array $known Authoritative rows keyed by identity.
	 * @return string|\WP_Error
	 */
	public static function match_row( $row, $known ) {
		$id = self::row_identity( $row );
		if ( isset( $known[ $id ] ) || 0 === strpos( $id, 'id:' ) ) {
			return $id;
		}
		$fields = array_map( array( __CLASS__, 'identity' ), $row['columns'] ?? array() );
		if ( empty( $fields ) ) {
			return $id;
		}
		$matches = array();
		foreach ( $known as $saved_id => $saved_row ) {
			if ( 0 === strpos( $saved_id, 'id:' ) ) {
				continue;
			}
			$saved_fields = array_map( array( __CLASS__, 'identity' ), $saved_row['columns'] ?? array() );
			if ( empty( array_diff( $fields, $saved_fields ) ) ) {
				$matches[] = $saved_id;
			}
		}
		return count( $matches ) > 1 ? self::unavailable() : ( $matches[0] ?? $id );
	}

	/**
	 * Foreign row values are opaque and read-only even with a field provider.
	 *
	 * @param array $candidate Proposed form.
	 * @param array $saved Authoritative form.
	 * @return array|\WP_Error
	 */
	public static function preserve_rows( $candidate, $saved ) {
		$known = array();
		foreach ( $saved['registrationFields'] ?? array() as $row ) {
			if ( ! is_array( $row ) ) {
				return self::unavailable();
			}
			$id = self::row_identity( $row );
			if ( isset( $known[ $id ] ) ) {
				return self::unavailable();
			}
			$known[ $id ] = $row;
		}
		$seen = array();
		foreach ( $candidate['registrationFields'] ?? array() as $index => $row ) {
			if ( ! is_array( $row ) ) {
				return self::unavailable();
			}
			$id = self::match_row( $row, $known );
			if ( is_wp_error( $id ) || isset( $seen[ $id ] ) ) {
				return self::unavailable();
			}
			$seen[ $id ] = true;
			$stored      = $known[ $id ] ?? array();
			foreach ( $row as $key => $value ) {
				if ( ! in_array( $key, self::standard_row_keys(), true ) && ( ! array_key_exists( $key, $stored ) || $stored[ $key ] !== $value ) ) {
					return self::unavailable();
				}
			}
			foreach ( $stored as $key => $value ) {
				if ( ! in_array( $key, self::standard_row_keys(), true ) ) {
					$candidate['registrationFields'][ $index ][ $key ] = $value;
				}
			}
		}
		return $candidate;
	}

	/**
	 * Preserve authoritative extension definitions and validate explicit changes.
	 * Omission preserves a record. Deletion requires _registrationProviderDeletes.
	 *
	 * @param array $candidate Sanitized submitted form.
	 * @param array $saved Authoritative form.
	 * @return array|\WP_Error
	 */
	public static function preserve_form( $candidate, $saved ) {
		$candidate = self::preserve_rows( $candidate, $saved );
		if ( is_wp_error( $candidate ) ) {
			return $candidate;
		}
		$deletes = $candidate['_registrationProviderDeletes'] ?? array();
		unset( $candidate['_registrationProviderDeletes'] );
		if ( ! is_array( $deletes ) ) {
			return self::unavailable();
		}
		$known   = array();
		$custom  = array();
		$changed = ! empty( $deletes );
		foreach ( self::fields( $saved ) as $field ) {
			$id = self::identity( $field );
			if ( isset( $known[ $id ] ) ) {
				return self::unavailable();
			}
			$known[ $id ] = $field;
			if ( ! WholesaleX_CommonUtils::is_standard_registration_field( $field ) ) {
				$custom[ $id ] = $field;
			}
		}
		$seen  = array();
		$names = array();
		foreach ( $candidate['registrationFields'] ?? array() as $row_index => $row ) {
			foreach ( $row['columns'] ?? array() as $index => $field ) {
				if ( ! is_array( $field ) ) {
					return self::unavailable();
				}
				$id = self::identity( $field );
				if ( isset( $seen[ $id ] ) ) {
					return self::unavailable();
				}
				$name = $field['name'] ?? null;
				if ( ! is_string( $name ) || isset( $names[ $name ] ) || in_array( $id, $deletes, true ) ) {
					return self::unavailable();
				}
				$names[ $name ] = true;
				$seen[ $id ]    = true;
				if ( isset( $custom[ $id ] ) ) {
					if ( $field !== $custom[ $id ] ) {
						$changed = true;
					}
				} elseif ( ! WholesaleX_CommonUtils::is_standard_registration_field( $field ) ) {
					$changed = true;
				} elseif ( ! isset( $known[ $id ] ) ) {
					foreach ( array_keys( $field ) as $key ) {
						if ( ! in_array( $key, self::standard_field_keys(), true ) ) {
							$changed = true;
						}
					}
				} elseif ( isset( $known[ $id ] ) ) {
					// Only standard-field presentation keys are writable by the base.
					$owned = self::standard_field_keys();
					foreach ( $field as $key => $value ) {
						if ( ! in_array( $key, $owned, true ) && ! array_key_exists( $key, $known[ $id ] ) ) {
							$changed = true;
						}
					}
					foreach ( $known[ $id ] as $key => $value ) {
						if ( ! in_array( $key, $owned, true ) ) {
							if ( array_key_exists( $key, $field ) && $field[ $key ] !== $value ) {
								$changed = true;
							}
							$candidate['registrationFields'][ $row_index ]['columns'][ $index ][ $key ] = $value;
						}
					}
				}
			}
		}
		$saved_rows = array();
		foreach ( $saved['registrationFields'] ?? array() as $row ) {
			$saved_rows[ self::row_identity( $row ) ] = $row;
		}
		$targets = array();
		foreach ( $candidate['registrationFields'] ?? array() as $index => $row ) {
			$targets[ self::match_row( $row, $saved_rows ) ] = $index;
		}
		foreach ( $saved_rows as $row_id => $row ) {
			$missing = array();
			foreach ( $row['columns'] ?? array() as $field ) {
				$id = self::identity( $field );
				if ( isset( $custom[ $id ] ) && ! isset( $seen[ $id ] ) && ! in_array( $id, $deletes, true ) ) {
					$missing[ $id ] = $field;
				}
			}
			if ( empty( $missing ) ) {
				continue;
			}
			if ( ! array_key_exists( $row_id, $targets ) ) {
				$candidate['registrationFields'][] = array_diff_key( $row, array( 'columns' => true ) );
				$targets[ $row_id ]                = array_key_last( $candidate['registrationFields'] );
			}
			$target  = $targets[ $row_id ];
			$columns = array();
			foreach ( $candidate['registrationFields'][ $target ]['columns'] ?? array() as $field ) {
				$columns[ self::identity( $field ) ] = $field;
			}
			$columns += $missing;
			$ordered  = array();
			foreach ( $row['columns'] ?? array() as $field ) {
				$id = self::identity( $field );
				if ( isset( $columns[ $id ] ) ) {
					$ordered[] = $columns[ $id ];
					unset( $columns[ $id ] );
				}
			}
			$candidate['registrationFields'][ $target ]['columns'] = array_merge( $ordered, array_values( $columns ) );
		}

		// Top-level foreign data is always authoritative, never round-tripped writable.
		$core = array_keys( WholesaleX_CommonUtils::get_empty_form() );
		foreach ( $saved as $key => $value ) {
			if ( ! in_array( $key, $core, true ) && 'registrationFields' !== $key ) {
				$candidate[ $key ] = $value;
			}
		}
		foreach ( array_keys( $candidate ) as $key ) {
			if ( ! in_array( $key, $core, true ) && 'registrationFields' !== $key && ! array_key_exists( $key, $saved ) ) {
				return self::unavailable();
			}
		}
		if ( $changed ) {
			$provider = self::get();
			if ( null === $provider ) {
				return self::unavailable();
			}
			$result = call_user_func(
				$provider['validate_form'],
				$candidate,
				$saved,
				array(
					'surface' => 'builder',
					'deletes' => $deletes,
				)
			);
			if ( true !== $result ) {
				return is_wp_error( $result ) ? $result : self::unavailable();
			}
		}
		return self::preserve_unknown( $candidate, $saved, self::base_form_shape() );
	}
}
