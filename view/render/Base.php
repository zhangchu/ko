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

    public function oSetData($vName, $vValue = null)
    {
        if (!is_array($vName))
        {
            $vName = array($vName => $vValue);
        }
        $this->_aData = array_merge($this->_aData, $vName);
        return $this;
    }
    
    public function sRender()
    {
        return '';
    }
}
