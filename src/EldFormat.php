<?php
/**
 * @copyright 2025 Nito T.M.
 * @license https://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 * @author Nito T.M. (https://github.com/nitotm)
 * @package nitotm/efficient-language-detector
 */

namespace Nitotm\Eld;

// TODO v4, delete file for v4
@trigger_error(
    'Class Nitotm\Eld\EldFormat is deprecated and will be removed in a future release. Use Nitotm\Eld\EldScheme instead.',
    E_USER_DEPRECATED
);

// Create an alias: EldFormat -> EldScheme
\class_alias(\Nitotm\Eld\EldScheme::class, \Nitotm\Eld\EldFormat::class);

