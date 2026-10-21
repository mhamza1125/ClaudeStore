<?php

namespace App\Assistant;

use RuntimeException;

/** The assistant cannot answer right now (not configured, rate limited, network or API error). */
class AssistantUnavailable extends RuntimeException {}
