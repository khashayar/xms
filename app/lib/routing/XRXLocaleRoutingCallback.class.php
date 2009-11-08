<?php

class XRXLocaleRoutingCallback extends AgaviRoutingCallback
{
	protected $availableLocales = array();

	/**
	 * Initialize the callback instance.
	 *
	 * @param      AgaviResponse An AgaviResponse instance.
	 * @param      array         An array with information about the route.
	 *
	 * @author     Khashayar Hajian <me@khashayar.me>
	 */
	public function initialize(AgaviContext $context, array &$route)
	{
		parent::initialize($context, $route);

		// Reduce method calls
		$this->translationManager = $this->getContext()->getTranslationManager();

		// Store the available locales, that's faster
		$this->availableLocales = $this->translationManager->getAvailableLocales();
	}

	/**
	 * Gets executed when the route of this callback route matched.
	 *
	 * @param      array                   The parameters generated by this route.
	 * @param      AgaviExecutionContainer The original execution container.
	 *
	 * @return     bool Whether the routing should handle the route as matched.
	 *
	 * @author     Khashayar Hajian <me@khashayar.me>
	 */
	public function onMatched(array &$parameters, AgaviExecutionContainer $container)
	{
		// Let's check if the locale is allowed
		try {
			$set = $this->translationManager->getLocaleIdentifier($parameters['locale']);
			// Yup, worked. now lets set that as a cookie
			$this->getContext()->getController()->getGlobalResponse()->setCookie('locale', $parameters['locale'], '+1 month');
			return true;
		} catch(AgaviException $e) {
			// Uregistered or ambigious locale... uncool!
			// onNotMatched will be called for us next
			return false;
		}
	}

	/**
	 * Gets executed when the route of this callback route did not match.
	 *
	 * @param      AgaviExecutionContainer The original execution container.
	 *
	 * @author     Khashayar Hajian <me@khashayar.me>
	 */
	public function onNotMatched(AgaviExecutionContainer $container)
	{
		// The pattern didn't matcb, or onMatched() returned false.
		// That's sad. let's see if there's a locale set in a cookie from an earlier visit.
		$cookie = $this->getContext()->getRequest()->getRequestData()->getCookie('locale');
		if($cookie !== null) {
			try {
				$this->translationManager->setLocale($cookie);
			} catch(AgaviException $e) {
				// bad cookie :<
				$this->getContext()->getController()->getGlobalResponse()->unsetCookie('locale');
			}
		}
	}

	/**
	 * Gets executed when the route of this callback is about to be reverse
	 * generated into an URL.
	 *
	 * @param      array The default parameters stored in the route.
	 * @param      array The parameters the user supplied to AgaviRouting::gen().
	 * @param      array The options the user supplied to AgaviRouting::gen().
	 *
	 * @return     bool  Whether this route part should be generated.
	 *
	 * @author     Khashayar Hajian <me@khashayar.me>
	 */
	public function onGenerate(array $defaultParameters, array &$userParameters, array &$options)
	{
		if(isset($userParameters['locale'])) {
			$userParameters['locale'] = $this->getShortestLocaleIdentifier($userParameters['locale']);
		} else {
			$userParameters['locale'] = $this->getShortestLocaleIdentifier($this->translationManager->getCurrentLocaleIdentifier());
		}
		return true;
	}

	public function getShortestLocaleIdentifier($localeIdentifier)
	{
		static $localeMap = null;
		if($localeMap === null) {
			foreach($this->availableLocales as $locale) {
				$localeMap[$locale['identifierData']['language']][] = $locale['identifierData']['territory'];
			}
		}

		if(count($localeMap[$short = substr($localeIdentifier, 0, 2)]) > 1) {
			return $localeIdentifier;
		} else {
			return $short;
		}
	}
}

?>