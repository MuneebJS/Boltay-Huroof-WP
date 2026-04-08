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

    // AJAX Push — timeout set to 5 minutes to allow large pushes
    $("#wpgp-push-form").on("submit", function (e) {
        e.preventDefault();

        var $btn = $("#wpgp-push-btn");
        var $spinner = $("#wpgp-push-spinner");
        var commitMessage = $("#wpgp_commit_message").val() || "Sync from WordPress";

        setLoading($btn, $spinner, true);
        showOutput({ status: "pushing", message: "Scanning files and pushing to GitHub…" });

        $.ajax({
            url: wpgpAdmin.ajaxUrl,
            method: "POST",
            timeout: 300000,
            data: {
                action: "wpgp_direct_push",
                nonce: wpgpAdmin.pushNonce,
                commit_message: commitMessage
            }
        })
        .done(function (res) {
            if (res && res.success) {
                showOutput({
                    status: "success",
                    commit: res.data.commit_sha,
                    files_pushed: res.data.files_pushed,
                    text_inline: res.data.text_inline,
                    blobs_created: res.data.blobs_created
                });
                setTimeout(function () { location.reload(); }, 2000);
            } else {
                showOutput({
                    status: "error",
                    message: (res && res.data && res.data.message) || "Unknown error"
                });
            }
        })
        .fail(function (xhr, textStatus) {
            var msg = "Push request failed";
            if (textStatus === "timeout") {
                msg = "Request timed out — the push may still be processing server-side. Refresh the page and check the debug log.";
            } else {
                try {
                    var body = JSON.parse(xhr.responseText);
                    if (body && body.data && body.data.message) msg = body.data.message;
                } catch (_) {}
            }
            showOutput({ status: "error", message: msg });
        })
        .always(function () {
            setLoading($btn, $spinner, false);
        });
    });

    // AJAX Pull — timeout set to 5 minutes
    $("#wpgp-pull-form").on("submit", function (e) {
        e.preventDefault();

        var $btn = $("#wpgp-pull-btn");
        var $spinner = $("#wpgp-pull-spinner");

        setLoading($btn, $spinner, true);
        showOutput({ status: "pulling", message: "Fetching repository tree and downloading files…" });

        $.ajax({
            url: wpgpAdmin.ajaxUrl,
            method: "POST",
            timeout: 300000,
            data: {
                action: "wpgp_direct_pull",
                nonce: wpgpAdmin.pullNonce
            }
        })
        .done(function (res) {
            if (res && res.success) {
                showOutput({
                    status: "success",
                    files_updated: res.data.changed,
                    files_skipped: res.data.skipped || 0
                });
                setTimeout(function () { location.reload(); }, 2000);
            } else {
                showOutput({
                    status: "error",
                    message: "Pull completed with errors",
                    report: (res && res.data && res.data.report) || res
                });
            }
        })
        .fail(function (xhr, textStatus) {
            var msg = "Pull request failed";
            if (textStatus === "timeout") {
                msg = "Request timed out — the pull may still be processing server-side. Refresh the page and check the debug log.";
            } else {
                try {
                    var body = JSON.parse(xhr.responseText);
                    if (body && body.data && body.data.message) msg = body.data.message;
                } catch (_) {}
            }
            showOutput({ status: "error", message: msg });
        })
        .always(function () {
            setLoading($btn, $spinner, false);
        });
    });

})(jQuery);
