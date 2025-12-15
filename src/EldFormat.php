<?php
/**
 * @copyright 2025 Nito T.M.
 * @license https://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 * @author Nito T.M. (https://github.com/nitotm)
 * @package nitotm/efficient-language-detector
 */

namespace Nitotm\Eld;

// TODO error for v3.X, delete file for v4
/*
@trigger_error(
    'Class Nitotm\Eld\EldFormat is deprecated and will be removed for v4. Use Nitotm\Eld\EldScheme instead.',
    E_USER_DEPRECATED
);
*/
// Create an alias: EldFormat -> EldScheme
\class_alias(EldScheme::class, EldFormat::class);
