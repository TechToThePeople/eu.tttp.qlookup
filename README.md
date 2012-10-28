This extension improves the default search in several ways:
- more efficient requests
- search in the start of the names not in the middle (first or last)
- adjust based on the lenght of the query and the number of results to do the query(ies) that are the most efficient

Give it a go and let me know what you think of the result.

## Implementation ##
The existing hook for the autocomplete is at the database level. 
This means that it either forces you to create a tempory table or to limit yourself to a single query.

This extension creates a new getgoodquick API action, that is used instead of the default autocomplete. This leverage the existing crmAutocomplete javascript.
 
