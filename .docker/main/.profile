# Remove /opt/drupal/vendor/bin from $PATH.
PATH=$(echo "$PATH" | sed -e "s/:\/opt\/drupal\/vendor\/bin//")
