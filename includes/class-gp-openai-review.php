<?php

class GP_OpenAI_Review {
	/**
	 * The OpenAI key.
	 *
	 * @var string
	 */
	private static $gp_openai_key = '';

	/**
	 * Get suggestions from OpenAI (ChatGPT).
	 *
	 * @param string $original_singular The singular from the original string.
	 * @param string $translation       The translation.
	 * @param string $locale            The locale.
	 * @param string $glossary_query   The prompt generated to include glossary for the locale.
	 *
	 * @return array
	 */
	public static function get_openai_review( $original_singular, $translation, $locale, $glossary_query ): array {
		$openai_query = '';
		$openai_key   = apply_filters( 'gp_get_openai_key', self::$gp_openai_key );

		if ( empty( trim( $openai_key ) ) ) {
			return array();
		}
		$openai_temperature = 0;

		$gp_locale     = GP_Locales::by_field( 'slug', $locale );
		$openai_query .= 'For the english text  "' . $original_singular . '", is "' . $translation . '" a correct translation in ' . $gp_locale->english_name . '?';

		$messages        = array(
			array(
				'role'    => 'system',
				'content' => $glossary_query,
			),
			array(
				'role'    => 'user',
				'content' => $openai_query,
			),
		);
		$openai_response = wp_remote_post(
			'https://api.openai.com/v1/chat/completions',
			array(
				'timeout' => 20,
				'headers' => array(
					'Content-Type'  => 'application/json',
					'Authorization' => 'Bearer ' . $openai_key,
				),
				'body'    => wp_json_encode(
					array(
						'model'       => 'gpt-3.5-turbo',
						'max_tokens'  => 1000,
						'n'           => 1,
						'messages'    => $messages,
						'temperature' => $openai_temperature,
					)
				),
			)
		);
		if ( is_wp_error( $openai_response ) ) {
			return array();
		}
		$response_status = wp_remote_retrieve_response_code( $openai_response );
		if ( 200 !== $response_status ) {
			return array();
		}
		$output = json_decode( wp_remote_retrieve_body( $openai_response ), true );

		$message                      = $output['choices'][0]['message'];
		$response['openai']['review'] = trim( trim( $message['content'] ), '"' );
		$response['openai']['diff']   = '';

		return $response;
	}
}
