<?php
/**
 * Base
 *
 * @package ko\view\render
 * @author jiangjw & zhangchu
 */

class Ko_View_Render_Base
{
    protected $_aData = array();

    private $_oNext = null;
    
    public function oSetData($vName, $vValue = null)
    {
        if (!is_array($vName))
        {
            $vName = array($vName => $vValue);
        }
        $this->_aData = array_merge($this->_aData, $vName);
        return $this;
    }
    
    public function oAppend(Ko_View_Render_Base $oNext)
    {
        if (null === $this->_oNext)
        {
            $this->_oNext = $oNext;
        }
        else
        {
            $this->_oNext->oAppend($oNext);
        }
    }

    public function sRender()
    {
        if (null !== $this->_oNext)
        {
            return $this->_sRender().$this->_oNext->sRender();
        }
        return $this->_sRender();
    }
    
    protected function _sRender()
    {
        return '';
    }
}
