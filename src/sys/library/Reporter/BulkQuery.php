<?php

namespace Reporter;

class BulkQuery extends Query {

    public function toString() {

        // Cf. https://developer.matomo.org/api-reference/reporting-api
        return urlencode('urls[]').'='.urlencode(parent::toString());
    }
}