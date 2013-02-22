<?php /*

[Folder]
id=cmis:folder
localName=folder
description=Folder type
baseId=cmis:folder
creatable=true
fileable=false
queryable=true
fulltextIndexed=true
includedInSupertypeQuery=true
controllablePolicy=false
controllableACL=false

[Frontpage]
id=cmis:frontpage
localName=frontpage
#queryName=frontpage
description=Frontpage type
baseId=cmis:folder
creatable=true
fileable=false
queryable=true
fulltextIndexed=true
includedInSupertypeQuery=true
controllablePolicy=false
controllableACL=false

[Image]
id=cmis:image
localName=image
description=Image type
baseId=cmis:document
creatable=true
fileable=true
queryable=true
fulltextIndexed=true
includedInSupertypeQuery=true
controllablePolicy=false
controllableACL=false
versionable=false
# A value that indicates whether a content-stream MAY, SHALL, or SHALL NOT be included in
# objects of this type. Values:
#     •   notallowed: A content-stream SHALL NOT be included
#     •   allowed: A content-stream MAY be included
#     •   required: A content-stream SHALL be included (i.e. SHALL be included when the object
#                   is created, and SHALL NOT be deleted.)
contentStreamAllowed=allowed
contentAttributeId=image

[File]
id=cmis:file
localName=file
description=File type
baseId=cmis:document
creatable=true
fileable=true
queryable=true
fulltextIndexed=true
includedInSupertypeQuery=true
controllablePolicy=false
controllableACL=false
versionable=false
contentStreamAllowed=allowed
contentAttributeId=file
# Alias for typeId.
# 'cmis:document' means the same with 'file' in this case
aliasList[]=cmis:document

*/ ?>