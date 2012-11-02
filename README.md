This extension improves the default search in several ways:
- more efficient requests
- search in the start of the names instead of in the middle (first or last)
- adjust based on the lenght of the query and the number of results to do the query(ies) that are the most efficient

Give it a go and let me know what you think of the result.

## Tips and trick ##
If you search for a number (eg. 42) it won't find any contact, but if you press enter, it will directly go to the contact view for this contact id.

It will not waste resources searching for the first 2 chars, so you won't get all the "x", then all the "xa" before getting all the "xav". 3 letters seems to be the right lenght for my users (between 3k and 100k contacts).

## Implementation ##
The existing hook for the autocomplete is at the database level. 
This means that it either forces you to create a tempory table or to limit yourself to a single query.

This extension creates a new getttpquick API action, that is used instead of the default autocomplete. This leverage the existing crmAutocomplete javascript.
