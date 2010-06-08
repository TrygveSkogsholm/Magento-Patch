<?php
/**
*This is the overwrite for the customer account controller class in mage.
change is in the login redirect.

If the customer is part of group number 2 (wholesale) then he gets ?___store=wholesaledefault added to his base url.
 */

/**
 * Customer account controller
 *
 * @author      Trygve a velo-orange employee>
 */
 
# Controllers are not autoloaded so we will have to do it manually: 
require_once 'Mage/Customer/controllers/AccountController.php';
class MDN_Customer_AccountController extends Mage_Customer_AccountController
{
    /**
     * Define target URL and redirect customer after logging in
     */
    protected function _loginPostRedirect()
    {
        $session = $this->_getSession();

        if (!$session->getBeforeAuthUrl() || $session->getBeforeAuthUrl() == Mage::getBaseUrl() ) {

            // Set default URL to redirect customer to
            $session->setBeforeAuthUrl(Mage::helper('customer')->getAccountUrl());

            // Redirect customer to the last page visited after logging in
            if ($session->isLoggedIn())
            {
                if (!Mage::getStoreConfigFlag('customer/startup/redirect_dashboard')) {
                    if ($referer = $this->getRequest()->getParam(Mage_Customer_Helper_Data::REFERER_QUERY_PARAM_NAME)) {
                        $referer = Mage::helper('core')->urlDecode($referer);
                        if ($this->_isUrlInternal($referer)) {
                            $session->setBeforeAuthUrl($referer);
                        }
                    }
                }
                else if ($session->getAfterAuthUrl()) {
                    $session->setBeforeAuthUrl($session->getAfterAuthUrl(true));
                }
            } else {
                $session->setBeforeAuthUrl(Mage::helper('customer')->getLoginUrl());
            }
        } else if ($session->getBeforeAuthUrl() == Mage::helper('customer')->getLogoutUrl()) {
            $session->setBeforeAuthUrl(Mage::helper('customer')->getDashboardUrl());
        }
        else {
            if (!$session->getAfterAuthUrl()) {
                $session->setAfterAuthUrl($session->getBeforeAuthUrl());
            }
            if ($session->isLoggedIn()) {
                $session->setBeforeAuthUrl($session->getAfterAuthUrl(true));
            }
        }
        $_myGroupId = Mage::getSingleton('customer/session')->getCustomerGroupId();
              if($_myGroupId == 2){
              $this->_redirectUrl($session->getBeforeAuthUrl(true).'?___store=wholesaledefault');
              }
              else
              {$this->_redirectUrl($session->getBeforeAuthUrl(true));}
    }

    /**
     * Customer logout action
     */
    public function logoutAction()
    {
        $this->_getSession()->logout()
            ->setBeforeAuthUrl(Mage::getUrl());

        $this->_redirect('*/*/logoutSuccess');
    }

    /**
     * Logout success page
     */
    public function logoutSuccessAction()
    {
        $this->loadLayout();
        $this->renderLayout();
    }
}
