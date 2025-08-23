document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.querySelector('.search-input');
    const clearButton = document.querySelector('.clear-btn');
    const searchForm = document.querySelector('.search-form');
    let suggestionsDropdown;

    if (searchInput) {
        // Create suggestions dropdown
        suggestionsDropdown = document.createElement('div');
        suggestionsDropdown.classList.add('suggestions-dropdown');
        searchForm.appendChild(suggestionsDropdown);

        searchInput.addEventListener('input', function() {
            const query = searchInput.value;

            if (query.length > 0) {
                if(clearButton) clearButton.style.display = 'block';

                // Fetch suggestions
                fetch(`fetch_suggestions.php?query=${query}`)
                    .then(response => response.json())
                    .then(suggestions => {
                        suggestionsDropdown.innerHTML = '';
                        if (suggestions.length > 0) {
                            suggestions.forEach(suggestion => {
                                const suggestionItem = document.createElement('div');
                                suggestionItem.classList.add('suggestion-item');
                                suggestionItem.textContent = suggestion;
                                suggestionItem.addEventListener('click', function() {
                                    searchInput.value = suggestion;
                                    suggestionsDropdown.style.display = 'none';
                                    searchForm.submit();
                                });
                                suggestionsDropdown.appendChild(suggestionItem);
                            });
                            suggestionsDropdown.style.display = 'block';
                        } else {
                            suggestionsDropdown.style.display = 'none';
                        }
                    });
            } else {
                if(clearButton) clearButton.style.display = 'none';
                suggestionsDropdown.style.display = 'none';
            }
        });

        if(clearButton) {
            clearButton.addEventListener('click', function() {
                searchInput.value = '';
                clearButton.style.display = 'none';
                suggestionsDropdown.style.display = 'none';
                searchInput.focus();
            });
        }


        // Hide suggestions when clicking outside
        document.addEventListener('click', function(e) {
            if (!searchForm.contains(e.target)) {
                suggestionsDropdown.style.display = 'none';
            }
        });

        // Initial check
        if (searchInput.value.length > 0 && clearButton) {
            clearButton.style.display = 'block';
        }
    }
});