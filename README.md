This extension improves the default search in several ways:
- more efficient requests
- search in the start of the names instead of in the middle (first or last)
- adjust based on the length of the query and the number of results to do the query(ies) that are the most efficient

Give it a go and let me know what you think of the result.

## Tips and trick ##
If you search for a number (eg. 42) it won't find any contact, but if you press enter, it will directly go to the contact view for this contact id.

It will not waste resources searching for the first 2 chars, so you won't get all the "x", then all the "xa" before getting all the "xav". 3 letters seems to be the right lenght for my users (between 3k and 100k contacts).

if you type @gmai or .com it will seek them in the email
## Implementation ##
The existing hook for the autocomplete is at the database level.  This means that it either forces you to create a tempory table or to limit yourself to a single query.

This extension creates a new getttpquick API action, that is used instead of the default autocomplete. This leverages the existing crmAutocomplete javascript.

## Adjust the width ##
If < 4.2.8 and if you want to adjust the width of the result, you need to apply this patch CRM-11624 the http://issues.civicrm.org/jira/secure/attachment/18970/setwidth.patch 
