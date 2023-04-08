/**
 * RT LiveSearch
 *
 * Is a plugin which utilizes native MyBB search functionality and provides result via ajax.
 * Very light and highly customizable plugin for your search queries.
 *
 * @package rt_livesearch
 * @author  RevertIT <https://github.com/revertit>
 * @license http://opensource.org/licenses/mit-license.php MIT license
 */

let LiveSearch = {
    keypress: (url, eventKey) =>
    {
        document.addEventListener('keydown', (event) =>
        {
            let target = event.target;
            if ((event.key === eventKey.toUpperCase() || event.key === eventKey.toLowerCase()) &&
                (target.tagName !== 'INPUT' && target.tagName !== 'TEXTAREA') &&
                !$.modal.isActive()
            )
            {
                MyBB.popupWindow(url);
                event.preventDefault();
                return false;
            }
        });
    },
    searchInput: (pluginClass, searchDelay) =>
    {
        let timeoutId;

        const inputClass = pluginClass + '_keywords';
        document.querySelector(inputClass).addEventListener("input", (event) =>
        {
            clearTimeout(timeoutId);

            const searchTerm = event.target.value.trim();

            if (searchTerm === "")
            {
                return;
            }

            timeoutId = setTimeout(async () =>
            {
                await LiveSearch.searchAjax(pluginClass)
            }, searchDelay);
        });
    },
    searchAjax: async (pC) =>
    {
        const container = document.querySelector(pC + '_container');
        const spinnerClass = document.querySelector(pC + '_spinner');
        const formClass = document.querySelector(pC + '_form');
        const viewAll = document.querySelector(pC + '_viewall');

        try
        {
            spinnerClass.innerHTML = spinner;

            // Get the form data
            const form = formClass;
            const formData = new FormData(form);

            // Make the first API request
            const do_search = await fetch(rootpath + '/search.php?' + new URLSearchParams(formData).toString(), {
                method: 'GET',
            });

            if ( !do_search.ok)
            {
                throw new Error(`HTTP error! status: $ do_search.status}`);
            }

            const data1 = await do_search.json();

            if (data1.errors)
            {
                throw new Error(data1.errors);
            }

            // Use data from first API to make second API request
            const show_results = await fetch(data1.url, {
                method: 'GET',
            });

            if (!show_results.ok)
            {
                throw new Error(`HTTP error! status: ${show_results.status}`);
            }

            const data2 = await show_results.json();

            let outputHTML = '';
            for (const item of data2.template)
            {
                outputHTML += item;
            }
            container.innerHTML = outputHTML;
            viewAll.innerHTML = `<a href="${data1.redirect_url}">${data2.view_all}</a>`;

            spinnerClass.innerHTML = '';
        }
        catch (error)
        {
            spinnerClass.innerHTML = '';
            container.innerHTML = `<small class="error_message">${error}</small>`;
        }
    }
};