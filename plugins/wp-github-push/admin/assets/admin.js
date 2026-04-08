(function ($) {
    "use strict";

    var $output = $("#wpgp-status-output");

    function showOutput(data) {
        if (!$output.length) return;
        $output.show().text(JSON.stringify(data, null, 2));
    }

    function setLoading($btn, $spinner, loading) {
        $btn.prop("disabled", loading);
        if (loading) {
            $spinner.addClass("is-active");
        } else {
            $spinner.removeClass("is-active");
        }
    }

    // AJAX Push
    $("#wpgp-push-form").on("submit", function (e) {
        e.preventDefault();

        var $btn = $("#wpgp-push-btn");
        var $spinner = $("#wpgp-push-spinner");
        var commitMessage = $("#wpgp_commit_message").val() || "Sync from WordPress";

        setLoading($btn, $spinner, true);
        showOutput({ status: "pushing", message: "Scanning files and pushing to GitHub…" });

        $.post(wpgpAdmin.ajaxUrl, {
            action: "wpgp_direct_push",
            nonce: wpgpAdmin.pushNonce,
            commit_message: commitMessage
        })
        .done(function (res) {
            if (res && res.success) {
                showOutput({
                    status: "success",
                    commit: res.data.commit_sha,
                    files_pushed: res.data.files_pushed
                });
                location.reload();
            } else {
                showOutput({
                    status: "error",
                    message: (res && res.data && res.data.message) || "Unknown error"
                });
            }
        })
        .fail(function (xhr) {
            var msg = "Push request failed";
            try {
                var body = JSON.parse(xhr.responseText);
                if (body && body.data && body.data.message) msg = body.data.message;
            } catch (_) {}
            showOutput({ status: "error", message: msg });
        })
        .always(function () {
            setLoading($btn, $spinner, false);
        });
    });

    // AJAX Pull
    $("#wpgp-pull-form").on("submit", function (e) {
        e.preventDefault();

        var $btn = $("#wpgp-pull-btn");
        var $spinner = $("#wpgp-pull-spinner");

        setLoading($btn, $spinner, true);
        showOutput({ status: "pulling", message: "Fetching repository tree and downloading files…" });

        $.post(wpgpAdmin.ajaxUrl, {
            action: "wpgp_direct_pull",
            nonce: wpgpAdmin.pullNonce
        })
        .done(function (res) {
            if (res && res.success) {
                showOutput({
                    status: "success",
                    files_updated: res.data.changed,
                    files_skipped: res.data.skipped || 0
                });
                location.reload();
            } else {
                showOutput({
                    status: "error",
                    message: "Pull completed with errors",
                    report: (res && res.data && res.data.report) || res
                });
            }
        })
        .fail(function (xhr) {
            var msg = "Pull request failed";
            try {
                var body = JSON.parse(xhr.responseText);
                if (body && body.data && body.data.message) msg = body.data.message;
            } catch (_) {}
            showOutput({ status: "error", message: msg });
        })
        .always(function () {
            setLoading($btn, $spinner, false);
        });
    });

})(jQuery);
