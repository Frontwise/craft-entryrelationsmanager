// On ready
$(function () {
    $('.entryrelationsmanager').on('click', '.trigger > td, .trigger > th', function(e) {
        $(this).closest('tr').next().toggle();
        $(this).closest('tr').find('.toggle').toggleClass('expanded');
    });

    var $siteMenuBtn = $('.sitemenubtn');
    var siteMenu = $siteMenuBtn.menubtn().data('menubtn').menu;

    // On change site menu button, get data
    siteMenu.on('optionselect', function(ev) {
        siteMenu.$options.removeClass('sel');
        var $option = $(ev.selectedOption).addClass('se');
        $siteMenuBtn.html($option.html());
        var siteId = $option.data('site-id');
        Craft.setLocalStorage('BaseElementIndex.siteId', siteId);
        Craft.postActionRequest('entry-relations-manager/fields/fields', { siteId: siteId }, function(response, textStatus) {
            $('.entryrelationsmanager').html(response.html);
        });
    });

    // Set initially chosen site
    var storedSiteId = Craft.getLocalStorage('BaseElementIndex.siteId');
    var $storedSiteOption = siteMenu.$options.filter('[data-site-id="' + storedSiteId + '"]:first');

    if ($storedSiteOption.length) {
        $storedSiteOption.trigger('click');
    }

    $('.entryrelationsmanager').on('click', '.remove', function(e) {
        if (!confirm("Are you sure you want to remove these relations?")) {
            return;
        }

        var siteId = Craft.getLocalStorage('BaseElementIndex.siteId');
        var fieldId = $(this).data('field-id');
        var targetId = $(this).data('target-id');

        Craft.postActionRequest('entry-relations-manager/fields/remove', { siteId: siteId, fieldId: fieldId, targetId: targetId }, function(response, textStatus) {
            if (textStatus == 'success') {
                $('.entryrelationsmanager').html(response.html);
                Craft.cp.displayNotice('Relations successfully removed');
            } else {
                Craft.cp.displayError('Unable to remove the relations, please refresh the page and try again');
            }
        });
    });

    $('.entryrelationsmanager').on('click', '.edit', function(e) {
        var siteId = Craft.getLocalStorage('BaseElementIndex.siteId');
        var fieldId = $(this).data('field-id');
        var targetId = $(this).data('target-id');
        var sources = $(this).data('sources');

        Craft.createElementSelectorModal('craft\\elements\\Entry', {
            sources: sources,
            showSiteMenu: false,
            criteria: {siteId: siteId},
            onSelect: function(elements){
                if (elements.length) {
                    var element = elements[0];
                    Craft.postActionRequest('entry-relations-manager/fields/replace', { siteId: siteId, fieldId: fieldId, targetId: targetId, newTargetId: element.id }, function(response, textStatus) {
                        if (textStatus == 'success') {
                            $('.entryrelationsmanager').html(response.html);
                            Craft.cp.displayNotice('Relations successfully replaced');
                        } else {
                            Craft.cp.displayError('Unable to replace the relations, please refresh the page and try again');
                        }
                    });
                }
            }
        });
    });
});