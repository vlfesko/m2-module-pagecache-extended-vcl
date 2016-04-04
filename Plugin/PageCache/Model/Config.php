<?php
/**
 * Vlfesko PageCacheExtendedVcl PageCache\Model\Config Plugin
 * for \Magento\PageCache\Model\Config
 *
 * Patches Varnish 3/4 VCL by adding Ctrl+F5 cache purge via browser
 *
 * @category  Vlfesko
 * @package   Vlfesko_PageCacheExtendedVcl
 * @author    Volodymyr Fesko <vladimir.fesko@gmail.com>
 * @copyright 2016 Volodymyr Fesko
 */

namespace Vlfesko\PageCacheExtendedVcl\Plugin\PageCache\Model;

/**
 * PageCache\Model\Config Plugin
 */
class Config
{
    /**
     * Update VCL generated in getVclFile method
     *
     * @param \Magento\PageCache\Model\Config $subject Original class (its interceptor actually)
     * @param mixed                           $result Original VCL result
     *
     * @return mixed
     */
    public function afterGetVclFile(\Magento\PageCache\Model\Config $subject, $result)
    {
        if (strpos($result, 'vcl 4.0') === false) {
            $updatedVclResult = $this->_updateVarnish3Vcl($result);
        } else {
            $updatedVclResult = $this->_updateVarnish4Vcl($result);
        }
        return $updatedVclResult;
    }

    /**
     * Patch Varnish 4 VCL
     *
     * @param string $result Original VCL
     *
     * @return mixed
     */
    protected function _updateVarnish4Vcl($result)
    {
        // Add ctrl+f5 purge
        // First line is unindented since value is replaced from indented position
        $vclPatch = <<<VCL_CTRL_F5
# Handle Ctrl-F5 by forcing a cache miss
    # On Debian Jessie (Varnish 4.0.2), this will keep the hit counter
    # rising even though it does the right thing
    if (req.http.Cache-Control ~ "no-cache" && client.ip ~ purge) {
        set req.hash_always_miss = true;
    }
    
    return (hash);
VCL_CTRL_F5;
        return str_replace('return (hash);', $vclPatch, $result);
    }

    /**
     * Patch Varnish 3 VCL
     *
     * @param string $result Original VCL
     *
     * @return mixed
     */
    protected function _updateVarnish3Vcl($result)
    {
        // Add ctrl+f5 purge
        $vclPatch = <<<VCL_CTRL_F5
    
# Process Ctrl-F5 requests
sub vcl_hit {
    if (req.http.Cache-Control ~ "no-cache" && client.ip ~ purge) {
        purge;
        return(restart);
    }
}
VCL_CTRL_F5;
        return $result . $vclPatch;
    }
}
